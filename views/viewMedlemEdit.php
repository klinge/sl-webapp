<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// include database and object files
include_once 'config/database.php';
include_once 'models/medlem.php';

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = "Ändra besättning";
include_once $APP_DIR . "/layouts/header.php";

// retrieve records here
$database = new Database();
$db = $database->getConnection();

$url = $_SERVER['REQUEST_URI'];
$urlParts = explode("/", $url); // Split the URL by "/"
$id = end($urlParts); // Get the last element (member ID)

$medlem = new Medlem($db, $id);
var_dump($medlem);
?>

HÄR KOMMER SJÄLVA INNEHÅLLET!


<!-- datatables js -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script>
    let dataTable = new DataTable('#memberTable');
</script>

<?php // footer
    include_once $APP_DIR . "/layouts/footer.php";
?>