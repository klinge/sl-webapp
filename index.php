<?php
//https://dannyvankooten.github.io/AltoRouter/
require __DIR__ . '/utils/AltoRouter.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/models/medlem.php';

$router = new AltoRouter();
$router->setBasePath('/sl-webapp');

$router->map( 'GET', '/', function() {
    require __DIR__ . '/views/home.php';
});

$router->map( 'GET', '/medlem', function() {
    require __DIR__ . '/views/viewMedlem.php';
});

$router->map( 'GET', '/segling', function() {
    require __DIR__ . '/viewSegling.php';
});

$router->map('GET', '/hello/[a:name]', function($name) {
    echo "Hello, " . $name;
  }, 'hello');

$match = $router->match();

// dispatch or throw 404 status
if( is_array($match) && is_callable( $match['target'] ) ) {
	call_user_func_array( $match['target'], $match['params'] );
} else {
	// no route was matched
	header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}

function getDatabaseConn() {
    // get database connection
    $database = new Database();
    return $database->getConnection();
}