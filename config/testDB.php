<?php 
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

require 'database.php';

$db = new Database();
$conn = $db->getConnection();
$thisId = 1;

function select($conn, $id) {
//TESTING A SIMPLE SELECT


$query = 'SELECT * FROM Medlem WHERE id = :id'; 

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
var_dump($stmt->fetchAll());

}

function update($conn, $id) {
//TESTING UPDATE
$fornamn = 'Johanna';

$query = 'UPDATE Medlem SET fornamn = :fornamn WHERE id = :id';
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->bindParam(':fornamn', $fornamn);
$stmt->execute();
}

select($conn, $thisId);
update($conn, $thisId);
select($conn, $thisId);