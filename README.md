HyperClient
===========

Please note:
============
As of 2013-01-30 We (FoxyCart) will no longer be using or maintaining this library. [GuzzlePHP](http://guzzlephp.org/) seems to fit our needs rather well so we don't feel this package is adding anything especially new to the dev community. If you're looking for an example of how to work with the FoxyCart API, everything here will continue to work as before. We'll also be creating new examples in the future using GuzzlePHP. If you have any questions, please feel free to contact us.
-----------------------------------------------------------------------------------

A simple CURL based REST client for PHP with local cache support.

There's also a [Ruby client](https://github.com/codegram/hyperclient) of the same name so please head over there if you're using Ruby.

For this client to work, your API must use [Web Linking](http://tools.ietf.org/html/rfc5988) headers. It comes with an example against the FoxyCart Hypermedia API sandbox. For more information on the API shown here, see the [FoxyCart documentation](http://wiki.foxycart.com/v/0.0.0/hypermedia_api). 

We'd love to know what you think about it. Feel free to ping [Luke Stokes](https://twitter.com/lukestokes) with any questions or comments.

##Install

HyperClient uses the Composer installer. If you're doing PHP, I highly recommend you get familiar with it. Along with PHP and curl, the client requires a caching mechanism to store responses with ETags. It currently includes a basic APC example, Redis example as well as a file based example. To use the file based cache, you must have a private folder (not readable by the web or others on your server) that you can write to and read from. See the comments in example.php for more information. Note: we require caching to emphasize the importance of ETags for scalability and performance. Hypermedia APIs will quickly become "chatty" if this isn't done.

To install and view the example page, follow these steps:

1. Download to a web-accessible directory on your server (such as hyperclient)
1. Install composer with this from your command line: curl -s https://getcomposer.org/installer | php
    (or see their latest docs for how to install composer: http://getcomposer.org/download/)
1. php composer.phar install
    (if you get an error about server permissions, you may need to do something like php -d "disable_functions=" composer.phar install)
1. Load up example.php in a browser (such as yourdomain.com/hyperclient/example.php) and you'll see some example live output from the FoxyCart API sandbox.

## Files

* src/HyperClient/Client.php: The generic client. Currently has some example caching implementations as well. Requires curl.
* src/HyperClient/cache/APC.php, src/HyperClient/cache/File.php, src/HyperClient/cache/Redis.php: Caching implementations.
* src/HyperClient/cache/Entry.php: simple interface for a cached response.
* ExampleDisplay.php: A class used for displaying the output from the client.
* example.php: This is the file you actually load up in your browser to see the example output. Important: it currently uses APC for caching. If you don't have APC installed, modify it as needed for File (or Redis) based cache.
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
require __DIR__ . '/vendor/autoload.php';

// setup APC cache
$cache = new HyperClient\cache\APC();
// or File based cache
// $cache = new HyperClient\cache\File('/path/to/my/cache/folder');
// or add your own... (it's really easy)

// if your API has a proper uri for its link relationships...
$rel_base_uri = 'https://api.foxycart.com/rels';

// setup client
// setup client
$client = new HyperClient\Client($cache,$rel_base_uri);

// do stuff!
$resp = $client->get('https://api-sandbox.foxycart.com',null,array('FOXYCART-API-VERSION' => 1));

print '<pre>' . htmlspecialchars(print_r($resp,true)) . '</pre>';
```

## Props

This was heavily inspired and influenced by Ed Finkler's [Resty library](https://github.com/fictivekin/resty.php). If you enjoy it, please [give him a shout out](https://twitter.com/funkatron).
