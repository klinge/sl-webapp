<?php

// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require 'vendor/autoload.php';
//Using AltoRouter for url routing: https://dannyvankooten.github.io/AltoRouter/
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/MedlemController.php';
require_once __DIR__ . '/controllers/SeglingController.php';
require_once __DIR__ . '/controllers/BetalningController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/utils/Session.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 1800);  // Session expires after 30 mins (in seconds)
Session::start();

$router = new AltoRouter();
$router->setBasePath('/sl-webapp');

$router->map( 'GET', '/', 'HomeController#index', 'home'); 

$router->map('GET', '/medlem', 'MedlemController#list', 'medlem-list');
$router->map('GET', '/medlem/[i:id]', 'MedlemController#edit', 'medlem-edit');
$router->map('POST', '/medlem/[i:id]', 'MedlemController#save', 'medlem-save');
$router->map('GET', '/medlem/new', 'MedlemController#new', 'medlem-new');
$router->map('POST', '/medlem/new', 'MedlemController#insertNew', 'medlem-create');
$router->map('POST', '/medlem/delete', 'MedlemController#delete', 'medlem-delete');

$router->map('GET', '/betalning', 'BetalningController#list', 'betalning-list');
$router->map('GET', '/betalning/[i:id]', 'BetalningController#getBetalning', 'betalning-edit');
$router->map('GET', '/betalning/medlem/[i:id]', 'BetalningController#getMedlemBetalning', 'betalning-medlem');
$router->map('POST', '/betalning/create', 'BetalningController#createBetalning', 'betalning-create');
$router->map('POST', '/betalning/delete/[i:id]', 'BetalningController#deleteBetalning', 'betalning-delete');

$router->map('GET', '/segling', 'SeglingController#list', 'segling-list');
$router->map('GET', '/segling/[i:id]', 'SeglingController#edit', 'segling-edit');
$router->map('POST', '/segling/[i:id]', 'SeglingController#save', 'segling-save');

$router->map('GET', '/login', 'AuthController#showLogin', 'show-login');
$router->map('POST', '/login', 'AuthController#login', 'login');
$router->map('GET', '/logout', 'AuthController#logout', 'logout');

$router->map('GET', '/betalning/auth', 'BetalningController#testAuth');

$match = $router->match();

if ($match === false) {
    echo "Ingen mappning för denna url";
    // here you can handle 404
} else {
    $request = $_SERVER;
    dispatch($match, $request, $router);
}

function dispatch($match, $request, $router) {
    //If we have a string with a # then it's a controller action pair
    if (is_string($match['target']) && strpos($match['target'], "#") !== false) {
        //Parse the match to get controller, action and params
        list( $controller, $action ) = explode( '#', $match['target'] );
        $params = $match['params'];

        //Check that the controller has the requested method and call it
        if ( method_exists($controller, $action) ) {
            $thisController = new $controller($request, $router);
            $thisController->{$action}($params);
        } 
        else {
            echo 'Error: can not call '. $controller.'#'.$action; 
            //possibly throw a 404 error
        }
    }
    //Handle the case then the target is a closure
    else if( is_array($match) && is_callable($match['target']) ) {
        call_user_func_array( $match['target'], $match['params'] );
    }
    else {
        header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    }

}