HyperClient
===========

A simple CURL based REST client with local cache support.

For this client to work, your API must use [Web Linking](http://tools.ietf.org/html/rfc5988) headers. It comes with an example against the FoxyCart Hypermedia API sandbox. For more information on the API shown here, see the [FoxyCart documentation](http://wiki.foxycart.com/v/0.0.0/hypermedia_api). 

We'd love to know what you think about it. Feel free to ping [Luke Stokes](https://twitter.com/lukestokes) with any questions or comments.

##Install

The client requires some form of caching mechanism to store responses with etags. It currently includes a basic APC example as well as a file based example. To use the file based cache, you must have a private folder (not readable by the web or others on your server) that you can write to and read from. See the comments for more information. 

## Files

* HyperClient.php: The generic client. Currently has a couple example caching implementations in it as well. Requires curl. This is the only file you need if you're looking for a Hypermedia API client.
* ExampleDisplay.php: A class used for displaying the output from the client.
* example.php: This is the file you actually load up in your browser to see the example output.
* Array2XML.php: used by ExampleDisplay.php to convert arrays to XML when using application/xml as a request content type.

## Running

Once you have things configured, simply fire up example.php to see the requests and responses. Feel free to fork it and play with your own examples. The basic commands are as follows:

    $resp = $client->get($uri,$data,$headers);
    $resp = $client->post($uri,$data,$headers);
    $resp = $client->put($uri,$data,$headers);
    $resp = $client->patch($uri,$data,$headers);
    $resp = $client->delete($uri,$data,$headers);

It comes with some nice hypermedia-friendly methods like $client->getLink($rel) and $client->getLocation(). It also formats the body of the response into either a SimpleXMLElement or json object. See the inline code comments for details.


```php
<?php
require __DIR__."/HyperClient.php";

// setup APC cache
$cache = new CacheAPC();
// or File based cache
// $cache = new CacheFile('/path/to/my/cache/folder');
// or add your own... (it's really easy)

// if your API has a proper uri for its link relationships...
$rel_base_uri = 'https://api.foxycart.com/rels';

// setup client
$client = new HyperClient($cache,$rel_base_uri);

// do stuff!
$resp = $client->get('https://api-sandbox.foxycart.com',null,array('FOXYCART-API-VERSION' => 1));

print '<pre>' . htmlspecialchars(print_r($resp,true)) . '</pre>';
```

## Props

This was heavily inspired and influenced by Ed Finkle's [Resty library](https://github.com/fictivekin/resty.php). If you enjoy it, please [give him a shout out](https://twitter.com/funkatron).