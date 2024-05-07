<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = $data['title'];
include_once $APP_DIR . "/layouts/header.php";
?>

<h4>Hello seglingEdit</h4>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>