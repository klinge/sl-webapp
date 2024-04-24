<?php
include_once '../config/database.php';
include_once '../models/medlem.php';

if (isset($_GET['id'])) {
    $memberId = $_GET['id'];
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $medlem = new Medlem($conn, $memberId);
    header('Content-Type: application/json; charset=utf-8');
    echo $medlem->getJson($memberId);
} 
else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array("error"=>"Missing data"));
}