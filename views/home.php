<?php 
$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];
// set page headers
$page_title = "STARTSIDAN";
include_once $APP_DIR . "/layouts/header.php";
?>

<h2>Detta är hemsidan</h2>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<?php // footer
    include_once $APP_DIR . "/layouts/footer.php";
?>