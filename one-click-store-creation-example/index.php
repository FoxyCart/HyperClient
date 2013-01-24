<?php
// Composer based autoloading (see: http://getcomposer.org/doc/04-schema.md#autoload)
require __DIR__ . '/vendor/autoload.php';

session_start();

/* FOXYCART API SETTINGS */
$api_home_page = 'https://api-sandbox.foxycart.com';
$rel_base_uri = 'https://api.foxycart.com/rels/';
$client = new HyperClient\Client(new HyperClient\cache\APC(),$rel_base_uri);

// Simple one-page app example of creating a store and working with it.

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$api_activity_log = '';
$error_message = '';

switch($action) {
    case 'logout':
        session_destroy();
        include 'view/logout.php';
        break;
    
    case 'create_store':
        $first_name = isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : '';
        $last_name = isset($_REQUEST['last_name']) ? $_REQUEST['last_name'] : '';
        $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
        $hostname = isset($_REQUEST['hostname']) ? $_REQUEST['hostname'] : '';

        if ($first_name == '' || $last_name == '' || $email == '' || $hostname == '') {
            $error_message = 'Some required information is missing.';
            include 'view/form.php';
            exit();
        }

        // If already created, pull from a database.
        if (!isset($_SESSION['client'])) {
            $resp = $client->get($api_home_page,null,getHeaders());
            $create_client_url = $client->getLink('create_client');
            $api_activity_log .= 'Creating client...<br />';
            try {
                $data = array(
                        'redirect_uri' => $hostname,
                        'project_name' => 'install_' . rand(),
                        'project_description' => 'FoxyCart Integration for ' . $hostname,
                        'company_name' => 'My Company',
                        'contact_name' => 'My Contact',
                        'contact_email' => 'contact@example.com',
                        'contact_phone' => '123456789'
                );
                $resp = $client->post($create_client_url,$data,getHeaders());
                $api_activity_log .= 'Status: ' . $resp['status'] . '<br />';
                if ($resp['status'] == '201') {
                    $client_uri = $client->getLocation();
                    // store this in the DB somewhere...
                    $_SESSION['client'] = $resp['data'];
                    $_SESSION['client_token_expires'] = time() + $resp['data']->token->expires_in;
                    $api_activity_log .= 'Client Created: <pre>' . $client->last_response['body'] . '</pre><br />';
                    // get the client id and secret used for refresh token and authorizations.
                    $resp = $client->get($client_uri,null,getHeaders($_SESSION['client']->token->access_token));
                    $_SESSION['client_data'] = $resp['data'];
                    $api_activity_log .= 'Client retrieved: <pre>' . $client->last_response['body'] . '</pre><br />';
                } else {
                    $error_message = 'Error creating FoxyCart OAuth Client: ' . $client->last_response['body'];
                    $error_message .= '<br/>Request:<br/>' . print_r($client->last_request['data'],true);
                    include 'view/form.php';
                    exit();
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                include 'view/form.php';
                exit();
            }
        }

        if (!isset($_SESSION['user'])) {
            $api_activity_log .= 'Creating user...<br />';
            // create FoxyCart user
            refreshTokenAsNeeded($_SESSION['client']->token);
            $resp = $client->get($api_home_page,null,getHeaders($_SESSION['client']->token->access_token));
            try {
                $create_user_url = $client->getLink('create_user');
                $data = array(
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                );
                $resp = $client->post($create_user_url,$data,getHeaders($_SESSION['client']->token->access_token));
                $api_activity_log .= 'Status: ' . $resp['status'] . '<br />';
                if ($resp['status'] == '201') {
                    // store this in the DB somewhere...
                    $_SESSION['user'] = $resp['data'];
                    $_SESSION['user_token_expires'] = time() + $resp['data']->token->expires_in;
                    $api_activity_log .= 'User Created: <pre>' . $client->last_response['body'] . '</pre><br />';
                } else {
                    $error_message = 'Error creating FoxyCart User: ' . $client->last_response['body'];
                    $error_message .= '<br/>Request:<br/>' . print_r($client->last_request['data'],true);
                    include 'view/form.php';
                    exit();
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                include 'view/form.php';
                exit();
            }
        }
        
        if (!isset($_SESSION['store'])) {
            $api_activity_log .= 'Creating store...<br />';
            // create FoxyCart store
            try {
                $create_store_url = $_SESSION['user']->{"_links"}->{$rel_base_uri . "stores"}->href;
                // Do this however you want. If the store_domain is not unique, the API will error.
                $url = parse_url($hostname);
                $store_domain = str_replace('.', '-', $url['host']) . '-' . rand();
                $store_name = 'FoxyCart Store for ' . $hostname;
                $data = array(
                        'store_name' => $store_name,
                        'store_domain' => $store_domain,
                        'store_url' => $hostname,
                        'store_email' => $email,
                        'store_postal_code' => '92646',
                        'store_country' => 'US',
                        'store_state' => 'CA'
                );
                refreshTokenAsNeeded($_SESSION['user']->token);
                $resp = $client->post($create_store_url,$data,getHeaders($_SESSION['user']->token->access_token));
                $api_activity_log .= 'Status: ' . $resp['status'] . '<br />';
                if ($resp['status'] == '201') {
                    // store this in the DB somewhere...
                    $_SESSION['store'] = $resp['data'];
                    $_SESSION['store_token_expires'] = time() + $resp['data']->token->expires_in;
                    $api_activity_log .= 'Store Created: <pre>' . $client->last_response['body'] . '</pre><br />';
                } else {
                    $error_message = 'Error creating FoxyCart Store: ' . $client->last_response['body'];
                    $error_message .= '<br/>Request:<br/>' . print_r($client->last_request['data'],true);
                    include 'view/form.php';
                    exit();
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
                include 'view/form.php';
                exit();
            }
        }
        
        include 'view/store.php';

        break;
    default:
        if (isset($_SESSION['store'])) {
            include 'view/store.php';
        } else {
            include 'view/form.php';
        }
        break;
}

function getHeaders($token = '')
{
    $headers = array(
            'FOXYCART-API-VERSION: 1'
    );
    if ($token != '') {
        $headers[] = 'Authorization: Bearer '. $token;
    }
    return $headers;
}

function refreshTokenAsNeeded(&$token)
{
    global $api_activity_log;
    global $client;
    $token_type = 'store';
    switch($token->scope) {
        case 'client_full_access':
            $token_type = 'client';
            break;
        case 'user_full_access':
            $token_type = 'user';
            break;
        case 'store_full_access':
            $token_type = 'store';
            break;
    }
    if (!isset($_SESSION[$token_type . '_token_expires']) || ($_SESSION[$token_type . '_token_expires'] - 30) < time()) {
        setOAuthTokenURL();
        $refresh_token_data = array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $token->refresh_token,
                'client_id' => $_SESSION['client_data']->client_id,
                'client_secret' => $_SESSION['client_data']->client_secret
        );
        $resp = $client->post($_SESSION['token_url'],$refresh_token_data);
        if ($resp['status'] == '200') {
            $api_activity_log .= 'Refresh Token Obtained: <pre>' . $client->last_response['body'] . '</pre><br />';
            $_SESSION[$token_type . '_token_expires'] = time() + $resp['data']->expires_in; 
            $token->access_token = $resp['data']->access_token;
            $token->refresh_token = $resp['data']->refresh_token;
        } else {
            $error_message = 'Error obtaining refresh token: ' . $client->last_response['body'];
            $error_message .= '<br/>Request:<br/>' . print_r($client->last_request['data'],true);
            include 'view/form.php';
            exit();
        }
    }
    
}

function setOAuthTokenURL()
{
    global $api_home_page;
    global $api_activity_log;
    global $client;
    if (!isset($_SESSION['token_url'])) {
        $resp = $client->get($api_home_page, null, array('FOXYCART_API_VERSION' => '1'));
        $_SESSION['token_url'] = $client->getLink('token');
    }
}
