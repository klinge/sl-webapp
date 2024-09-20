<?php

// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once __DIR__ . '/../vendor/autoload.php';

$app = new App\Application();
$app->runMiddleware();
$app->run();
exit;