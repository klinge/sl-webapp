<?php

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = "Ange nytt lösenord";
include_once $APP_DIR . "/layouts/header.php";
?>

Sätt nytt lösenord..

<?php // footer
    include_once $APP_DIR . "/layouts/footer.php";
?>