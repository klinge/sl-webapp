<?php

// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require 'vendor/autoload.php';
//Using AltoRouter for url routing: https://dannyvankooten.github.io/AltoRouter/
require __DIR__ . '/controllers/TestController.php';
require __DIR__ . '/controllers/MedlemController.php';

ini_set('session.cookie_lifetime', 3600);  // Session expires after 1 hour (in seconds)
session_start();

$router = new AltoRouter();
$router->setBasePath('/sl-webapp');

$router->map( 'GET', '/', function() {
    require __DIR__ . '/views/home.php';
});

$router->map('GET', '/medlem', 'MedlemController#list', 'medlem-lista');
$router->map('GET', '/medlem/[i:id]', 'MedlemController#edit', 'medlem-edit');
$router->map('POST', '/medlem/[i:id]', 'MedlemController#save', 'medlem-save');

$router->map( 'GET', '/segling', function() {
    require __DIR__ . '/views/viewSegling.php';
});

$router->map('GET', '/hello', 'TestController#hello', 'hello');
$router->map('GET', '/hello/[a:name]', 'TestController#helloName', 'helloName');
$router->map( 'GET', '/test', function() {
    echo "Hello from a closure!";
});
$router->map( 'GET', '/form', function() {
    require __DIR__ . '/views/home.php';
});
$router->map( 'POST', '/form', function() {
    var_dump($_POST);
});

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