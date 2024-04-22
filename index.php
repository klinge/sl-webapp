<?php
include_once "router.php";
include_once 'config/database.php';
include_once 'models/medlem.php';

//create router instance
$router = new Router();

$router->addRoute('GET', '/medlem', function () {
    $targetURL = "medlem.php";
    // Send the Location header with a 302 status code (temporary redirect)
    header("Location: $targetURL", true, 302);
});

$router->addRoute('GET', '/medlem/:id', function ($id) {
    $conn = getDatabaseConn();
    $medlem = new Medlem($conn, $id);
    echo $medlem->getJson($id);
});

function getDatabaseConn() {
    // get database connection
    $database = new Database();
    return $database->getConnection();
}

$router->matchRoute();