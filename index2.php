<?php
include_once "router.php";
include_once 'config/database.php';
include_once 'models/medlem.php';

// get database connection
$database = new Database();
$conn = $database->getConnection();

//create router instance
$router = new Router();

$router->addRoute('GET', '/medlem', function () {
    echo "TODO-Lista alla medlemmar!";
    exit;
});

$router->addRoute('GET', '/medlem/:id', function ($id) {
    $database = new Database();
    $conn = $database->getConnection();
    $medlem = new Medlem($conn, $id);
    echo $medlem->getJson($id);
    exit;
});

$router->matchRoute();