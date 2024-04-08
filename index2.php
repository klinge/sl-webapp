<?php
include_once "router.php";
include_once 'config/database.php';
include_once 'models/medlem.php';
include_once 'models/medlemRepository.php';

//create router instance
$router = new Router();

$router->addRoute('GET', '/medlem', function () {
    $conn = getDatabaseConn();
    $medlemmar = new medlemRepository($conn);
    echo $medlemmar->getAllJson();
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