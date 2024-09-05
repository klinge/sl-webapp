<?php

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = "Ange nytt lösenord";
include_once $APP_DIR . "/layouts/header.php";
?>

TODO Formulär för att ange nytt lösenord..<br /><br />

Användarens email är: <?php echo $viewData['email']; ?><br />
Token är <?php echo $viewData['token']; ?><br />

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>