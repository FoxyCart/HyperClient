HyperClient
===========

A simple CURL based REST client with local cache support

For this client to work, your API must use [Web Linking](http://tools.ietf.org/html/rfc5988) headers. It comes with an example against the FoxyCart Hypermedia API sandbox. For more information on the example API, see the [FoxyCart documentation](http://wiki.foxycart.com/v/0.0.0/hypermedia_api). 

##Install

The client requires some form of caching mechanism to store responses with etags. It currently includes a basic APC example as well as a file based example. To use the file based cache, you must have a private folder (not readable by the web or others on your server) that you can write to and read from. See the comments for more information. 
