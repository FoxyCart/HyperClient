<?php
// Composer based autoloading (see: http://getcomposer.org/doc/04-schema.md#autoload)
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/ExampleDisplay.php';

/*************** CONFIGURATION *******************/

/**
 * Put the API home page here.
 * @var string
 */
$api_home_page = 'https://api-sandbox.foxycart.com';

/**
 * The base URI for your Link Relations (you are using a full URI, right?).
 * @var string
 */
$rel_base_uri = 'https://api.foxycart.com/rels/';

/**
 * If you're using a file-based caching system, set this to a directory that is NOT IN THE WEB ROOT (i.e., not accessibly via the internet)
 * The directory needs to be readable and writable by whichever user your web server is running as. If you're on shared hosting, take
 * care to ensure this directory is not accessible by others on your server.
 * The example below should be changed based on your environment.
 * @var string
 */
$writeable_cache_directory = __DIR__ . '/cache'; // CHANGE ME!

/**
 * The type of caching system you'll be using.
 * @var string valid entries currently include "File" and "APC" (case sensitive!)
 */
$cache_type = 'APC';

/**
 * List your API's supported media types here
 * @var array
 */
$supported_media_types = array('hal','vnd.siren');

/*************** END CONFIGURATION *******************/

// setup caching
$cache = null;
$cache_class = 'HyperClient\\cache\\' . $cache_type;
if (class_exists($cache_class)) {
    if ($cache_type == 'File') {
        $cache = new $cache_class($writeable_cache_directory);
    } else {
        $cache = new $cache_class();
    }
} else {
    throw new Exception('The cache type you defined can\'t be found as a valid class: ' . $cache_type);
}

// setup client
$client = new HyperClient\Client($cache,$rel_base_uri);

// setup display
$display = new ExampleDisplay();
if (isset($_GET['media_type']) && in_array($_GET['media_type'],$supported_media_types)) {
    $display->setMediaType($_GET['media_type']);
}
if (isset($_GET['format']) && in_array($_GET['format'],array('json','xml'))) {
    $display->setFormat($_GET['format']);
}
if (isset($_GET['forwiki']) && $_GET['forwiki'] == 1) {
    $display->setForWiki(true);
}

// OK, now for the fun stuff...
$display->displayBegin();

$useful_links = array();
$tokens = array();

$resp = $client->get($api_home_page,null,$display->getHeaders());
$display->displayResult('Home Page',$client);
$useful_links['create_client'] = $client->getLink('create_client');
$useful_links['resources'] = $client->getLink('resources');
$useful_links['rels'] = $client->getLink('rels');

$resp = $client->get($useful_links['resources'],null,$display->getHeaders());
$display->displayResult('Resources',$client);
$useful_links['regions'] = $client->getLink('regions');

$resp = $client->get($useful_links['regions'],'country_code=CA',$display->getHeaders());
$display->displayResult('Resources: Regions',$client);

$resp = $client->get($useful_links['rels'],null,$display->getHeaders());
$display->displayResult('Link Relations',$client);

$resp = $client->get($useful_links['rels'] . '/client',null,$display->getHeaders());
$display->displayResult('Link Relations: Client',$client);

$resp = $client->post($useful_links['create_client'],null,$display->getHeaders());
$display->displayResult('Create Client, no data sent (ERROR)',$client);

$data = array(
        'redirect_uri' => 'http://example.com',
        'project_name' => 'my_project_' . rand(),
        'project_description' => 'some_awesome_project',
        'company_name' => 'foobar',
        'contact_name' => 'me',
        'contact_email' => 'test@example.com',
        'contact_phone' => '123456789'
);
$data = $display->formatRequestData($data);
$resp = $client->post($useful_links['create_client'],$data,$display->getHeaders());
$display->displayResult('Create Client',$client);
// in a real application, you'd save this whole token in your system somewhere
$tokens['client'] = $display->getToken($resp);

$resp = $client->get($api_home_page,null,$display->getHeaders($tokens['client']));
$display->displayResult('Home Page, Client Auth',$client);
$useful_links['client'] = $client->getLink('client');
$useful_links['create_user'] = $client->getLink('create_user');

$resp = $client->get($useful_links['client'],null,$display->getHeaders($tokens['client']));
$display->displayResult('Client',$client);

$resp = $client->post($useful_links['create_user'],null,$display->getHeaders($tokens['client']));
$display->displayResult('Create User, no data sent (ERROR)',$client);

$data = array(
        'first_name' => 'foo',
        'last_name' => 'bar',
        'email' => 'example_' . rand() . '@example.com',
);
$data = $display->formatRequestData($data);
$resp = $client->post($useful_links['create_user'],$data,$display->getHeaders($tokens['client']));
$display->displayResult('Create User',$client);
// in a real application, you'd save this whole token in your system somewhere
$tokens['user'] = $display->getToken($resp);
$useful_links['user'] = $client->getLocation();

$resp = $client->get($useful_links['user'],null,$display->getHeaders($tokens['client']));
$display->displayResult('User',$client);
$useful_links['stores'] = $client->getLink('stores');
$useful_links['user_attributes'] = $client->getLink('attributes');

$resp = $client->post($useful_links['stores'],null,$display->getHeaders($tokens['user']));
$display->displayResult('Create Store, no data sent (ERROR)',$client);

$data = array(
        'store_name' => 'foo',
        'store_domain' => 'foo' . rand() . 'bar',
        'store_url' => 'http://example.com',
        'store_email' => 'example@example.com',
        'store_postal_code' => '92646',
        'store_country' => 'US'
);
$data = $display->formatRequestData($data);
$resp = $client->post($useful_links['stores'],$data,$display->getHeaders($tokens['user']));
$display->displayResult('Create Store, no region (ERROR)',$client);

$data = array(
        'store_name' => 'foo',
        'store_domain' => 'foo' . rand() . 'bar',
        'store_url' => 'http://example.com',
        'store_email' => 'example@example.com',
        'store_postal_code' => '92646',
        'store_country' => 'US',
        'store_state' => 'CA'
);
$data = $display->formatRequestData($data);
$resp = $client->post($useful_links['stores'],$data,$display->getHeaders($tokens['user']));
$display->displayResult('Create Store',$client);
$tokens['store'] = $display->getToken($resp);
$useful_links['store'] = $client->getLocation();

$resp = $client->get($useful_links['store'],null,$display->getHeaders($tokens['store']));
$display->displayResult('Store',$client);

$resp = $client->post($useful_links['user_attributes'],null,$display->getHeaders($tokens['user']));
$display->displayResult('Create User Attribute, no data sent (ERROR)',$client);

$data = array(
        'name' => 'name=test_attribute_' . rand(),
        'value' => 'awesome',
);
$data = $display->formatRequestData($data);
$resp = $client->post($useful_links['user_attributes'],$data,$display->getHeaders($tokens['user']));
$display->displayResult('Create User Attribute',$client);
$useful_links['user_attribute'] = $client->getLocation();

$resp = $client->get($useful_links['user_attribute'],null,$display->getHeaders($tokens['user']));
$display->displayResult('User Attribute',$client);

$data = array(
        'value' => 'awesomer',
);
$data = $display->formatRequestData($data);
$resp = $client->patch($useful_links['user_attribute'],$data,$display->getHeaders($tokens['user']));
$display->displayResult('PATCH User Attribute',$client);

$display->displayEnd();
