<?php

// include database and object files
include_once 'config/database.php';
include_once 'models/medlem.php';
  
// get database connection
$database = new Database();
$db = $database->getConnection();
  
// pass connection to objects
$medlem = new Medlem($db, 4);
echo $medlem->fornamn . " " . $medlem->efternamn . ", ";
foreach ($medlem->roller as $roll) {
    echo $roll["roll_namn"] . ", ";
}

?>