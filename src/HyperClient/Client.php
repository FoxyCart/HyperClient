<?php

namespace HyperClient;

/**
 * A Simple Curl based REST Client.
 * If your API outputs link headers and either a json or xml format for the body
 * (custom or otherwise), this client should be able to make basic HTTP calls to it and parse the response
 * for you. It also requires some form of local cache mechanism to take advantage of HTTP 304 Not Modified.
 *
 * @link https://github.com/FoxyCart
 * @author Luke Stokes
 */
class Client
{
    /**
     * This holds our curl_init
     * @var curl handle
     */
    public $ch;

    /**
     * This array holds the details of the last request including:
     *     method
     *     data
     *     headers
     *     uri
     * @var array
     */
    public $last_request = array();

    /**
     * This array holds the details of the last response including:
     *     body
     *     header
     *     result (array)
     *         error (curl error)
     *         error_msg (curl error message)
     *         status (http status code)
     *         headers (array of headers in a key => value format)
     *         data (parsed body either the results of json_decode or SimpleXMLElement)
     * @var array
     */
    public $last_response = array();

    /**
     * List of registered link relations that don't need our rel_base_uri
     * TODO: Add a bunch of registered values here so we can use them in the future.
     * @var array
     */
    public $registered_link_relations = array('self','first','prev','next','last');

    /**
     * This is the base uri of our non-registered link relations. Example: https://api.foxycart.com/rels/
     * @var string
     */
    public $rel_base_uri = '';

    /**
     * Our custom caching mechanism following the iCache interface
     * This class implements iCache and must support the following methods:
     *     exists
     *     fetch
     *     store
     * @var iCache
     */
    private $cache;

    /**
     * If your client and server natively support HTTP PATCH, you can set this to true.
     * @var boolean
     */
    private $natively_supports_patch = false;

    /**
     * Our constructor. Requires a cache agent.
     * @param iCache $cache
     * @param string $rel_base_uri
     */
    public function  __construct(\HyperClient\interfaces\iCache $cache, $rel_base_uri = '') {
        $this->cache = $cache;
        $this->rel_base_uri = $rel_base_uri;
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->ch, CURLOPT_HEADER, TRUE);
        curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    }

    /**
     * Clean up after yourself
     */
    public function  __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * send a POST request
     * @param string $uri
     * @param array/string $fields array or http_build_query string of data sent in the query string for this request
     * @param array $headers array of header strings (0 => $key: $value or $key => $value)
     */
    function post($uri,$fields = null,$headers = null)
    {
        return $this->go('POST',$headers,$fields,$uri);
    }
    /**
     * send a GET request
     * @param string $uri
     * @param array/string $fields array or http_build_query string of data sent in the query string for this request
     * @param array $headers array of header strings (0 => $key: $value or $key => $value)
     */
    function get($uri,$fields = null,$headers = null)
    {
        return $this->go('GET',$headers,$fields,$uri);
    }
    /**
     * send a DELETE request
     * @param string $uri
     * @param array/string $fields array or http_build_query string of data sent in the query string for this request
     * @param array $headers array of header strings (0 => $key: $value or $key => $value)
     */
    function delete($uri,$fields = null,$headers = null)
    {
        return $this->go('DELETE',$headers,$fields,$uri);
    }
    /**
     * send a PUT request
     * @param string $uri
     * @param array/string $fields array or http_build_query string of data sent in the query string for this request
     * @param array $headers array of header strings (0 => $key: $value or $key => $value)
     */
    function put($uri,$fields = null,$headers = null)
    {
        return $this->go('PUT',$headers,$fields,$uri);
    }
    /**
     * send a PATCH request
     * @param string $uri
     * @param array/string $fields array or http_build_query string of data sent in the query string for this request
     * @param array $headers array of header strings (0 => $key: $value or $key => $value)
     */
    function patch($uri,$fields = null,$headers = null)
    {
        if (!$this->natively_supports_patch) {
            $headers = !is_array($headers) ? array() : $headers;
            $headers['X-HTTP-Method-Override'] = 'PATCH';
        }
        return $this->post($uri,$fields,$headers);
    }

    /**
     * This is the core processor for the various HTTP methods.
     * @param string $method
     * @param array $headers
     * @param array (or string) $fields
     * @param string $uri
     * @return array $result containing the following values:
     *         error (curl error)
     *         error_msg (curl error message)
     *         status (http status code)
     *         headers (array of headers in a key => value format)
     *         data (parsed body either the results of json_decode or SimpleXMLElement)
     */
    function go($method,$headers,$fields,$uri)
    {
        $request_uri = $uri;
        $headers = !is_array($headers) ? array() : $headers;
        if (is_array($fields)) {
            $fields = http_build_query($fields);
        }

        if ($method == 'GET') {
            if ($fields && $fields != '') {
                if(strpos($request_uri, '?') !== false) {
                    $request_uri .= '&' . $fields;
                } else {
                    $request_uri .= '?' . $fields;
                }
            }
            $fields = null;
        }
         
        $this->last_request['method'] = $method;
        $this->last_request['headers'] = $headers;
        $this->last_request['data'] = $fields;
        $this->last_request['uri'] = $request_uri;

        $cached_response = null;
        $cache_key = null;

        // if we're not sending an eTag, check our cache for it
        if (!array_key_exists('If-None-Match', $headers)) {
            $cache_key = $this->getCacheKeyForLastRequest();
            if ($this->cache->exists($cache_key)) {
                $cached_response = $this->cache->fetch($cache_key);
                $headers['If-None-Match'] = $cached_response->etag;
            }
        }

        curl_setopt($this->ch, CURLOPT_URL, $request_uri);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getHeadersArrayForTransport($headers));
        $response = curl_exec($this->ch);
        $info = curl_getinfo($this->ch);

        $this->last_response['header'] = '';
        $this->last_response['body'] = '';

        if ($response) {
            list($this->last_response['header'], $this->last_response['body']) = explode("\r\n\r\n", $response, 2);
            if (preg_match('/(Content-Type: )(.*$)/m',$this->last_response['header'],$matches)) {
                $this->last_response['content_type'] = $matches[2];
            }
            if ($info['http_code'] == '304' && !is_null($cached_response)) {
                // if we don't have a cached_response, we assume the app is doing it's own 304 handling.
                $this->last_response['header'] = $cached_response->header;
                $this->last_response['body'] = $cached_response->body;
                $this->last_response['content_type'] = $cached_response->content_type;
            }
        }

        $result = array();
        $result['error'] = curl_errno($this->ch);
        $result['error_msg'] = curl_error($this->ch);
        $result['status'] = $info['http_code'];
        $result['headers'] = $this->getHeadersArray();
        $result['data'] = $this->getData();

        if ($result['status'] == '200' && $method == 'GET' && array_key_exists('ETag',$result['headers'])) {
            $this->cacheLastResponse($result['headers']['ETag']);
        }

        $this->last_response['result'] = $result;

        return $result;
    }

    /**
     * Converts the raw header response to an array of $key => $value.
     * Note, $value may also be an array (such as with multiple link headers).
     * Stolen from Resty's metaToHeaders method
     * @link https://github.com/fictivekin/resty.php
     * @author Ed Finkler
     * @returns array
     */
    function getHeadersArray()
    {
        $headers = array();
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $this->last_response['header']) as $line){
            preg_match("|^([^:]+):\s?(.+)$|", $line, $matches);
            if (is_array($matches) && isset($matches[2])) {
                $header_key = trim($matches[1]);
                $header_value = trim($matches[2], " \t\n\r\0\x0B");
                if (isset($headers[$header_key])) {
                    if (is_array($headers[$header_key])) {
                        $headers[$header_key][] = $header_value;
                    } else {
                        $previous_entry = $headers[$header_key];
                        $headers[$header_key] = array($previous_entry, $header_value);
                    }
                } else {
                    $headers[$header_key] = $header_value;
                }
            }
        }
         
        return $headers;
    }

    /**
     * Inspired by Ed Finkler's Resty and buildHeadersArray
     *
     * Takes an array of either key/val pairs or raw header strings and builds an array of raw header strings
     *
     * @param array $headers
     * @return array
     * @author Ed Finkler
     */
    protected function getHeadersArrayForTransport($headers) {
        $headers_for_transport = array();
        if (!is_null($headers)) {
            foreach ($headers as $key => $value) {
                if (is_numeric($key)) {
                    $headers_for_transport[] = $value;
                } else {
                    $headers_for_transport[] = "{$key}: {$value}";
                }
            }
        }
        return $headers_for_transport;
    }

    /**
     * Get the href of a link header via the short rel code (example: for https://api.foxycart.com/rels/store, pass in store)
     * @param string $rel
     */
    public function getLink($rel)
    {
        $linkObj = $this->getLinkObj($rel);
        return $linkObj->href;
    }

    /**
     * Get the type of a link header via the short rel code (example: for https://api.foxycart.com/rels/store, pass in store)
     * @param string $rel
     */
    public function getLinkType($rel)
    {
        $linkObj = $this->getLinkObj($rel);
        return $linkObj->type;
    }

    /**
     * Get a stdClass with an href and type property obtained via the $rel shortcode
     * Always wrap this in a try/catch as it will throw an exception if the link is not found.
     * @param string $rel
     * @returns @stdClass
     * @throws Exception
     */
    private function getLinkObj($rel)
    {
        if (!in_array($rel, $this->registered_link_relations)) {
            // this is a bit of a hack for how we list our rels within the API itself, i.e. https://api.foxycart.com/rels
            if ($rel == 'rels') {
                $rel = trim($this->rel_base_uri,'/');
            } else {
                $rel = $this->rel_base_uri . $rel;
            }
        }
        $linkObj = new \stdClass();
        $linkObj->href = '';
        $linkObj->type = '';
        if (preg_match('|(link: <)(.*?)(>;rel="' . $rel . '";)(.*?)(?:(;type=")(.*?)("))?|', $this->last_response['header'], $matches)) {
            $linkObj->href = $matches[2];
            if (isset($matches[6])) {
                $linkObj->type = $matches[6];
            }
        } else {
            // TODO: Should we check the body response for hypermedia, though that would require a parser with format knowledge?
            // TODO: Should we create custom exception types?
            throw new \Exception('Link relation ' . $rel . ' not found.');
        }
        return $linkObj;
    }

    /**
     * Shortcut to return the location header.
     * Always wrap this in a try/catch as it will throw an exception if the location is not found.
     * @return string
     * @throws Exception
     */
    function getLocation()
    {
        $location_href = '';
        if (array_key_exists('Location',$this->last_response['result']['headers'])) {
            $location_href = trim($this->last_response['result']['headers']['Location']);
        } else if (array_key_exists('location',$this->last_response['result']['headers'])) {
            $location_href = trim($this->last_response['result']['headers']['location']);
        } else {
            // TODO: Should we create custom exception types?
            throw new \Exception('Location header not found.');
        }
        return $location_href;

    }

    /**
     * Simple method for determining if the response content type has "xml" in the name
     * @return boolean
     */
    function isXMLContentType()
    {
        return (strpos($this->last_response['content_type'],'xml') !== false);
    }

    /**
     * Simple method for determining if the response content type has "json" in the name
     * @return boolean
     */
    function isJSONContentType()
    {
        return (strpos($this->last_response['content_type'],'json') !== false);
    }

    /**
     * Formats the content body and returns either a json_decoded object or a SimpleXMLElement object
     * @return multitype:|\SimpleXMLElement|mixed|null
     */
    function getData()
    {
        if ($this->last_response['content_type'] == '') {
            return $this->last_response['body'];
        }
        if ($this->last_response['body'] != '') {
            if ($this->isXMLContentType()) {
                $xmlObj = new \SimpleXMLElement($this->last_response['body']);
                return $xmlObj;
            }
            if ($this->isJSONContentType()) {
                return json_decode($this->last_response['body']);
            }
        } else {
            return null;
        }
    }

    /**
     * returns a key that can be used for caching a request. The key includes all headers and the uri
     * TODO: only include the Vary header values in the key
     * @return string
     */
    function getCacheKeyForLastRequest()
    {
        return 'HC_' . md5($this->last_request['uri'] . ':' . serialize($this->last_request['headers']));
    }

    /**
     * Caches the last response given an ETag string
     * @param string $etag
     */
    function cacheLastResponse($etag)
    {
        $entry = new cache\Entry();
        $entry->etag = $etag;
        $entry->body = $this->last_response['body'];
        $entry->header = $this->last_response['header'];
        $entry->content_type = $this->last_response['content_type'];
        $this->cache->store($this->getCacheKeyForLastRequest(), $entry);
    }

}