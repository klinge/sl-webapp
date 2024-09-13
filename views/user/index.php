<?php
$page_title = "";
$APP_DIR = $_SERVER['DOCUMENT_ROOT'] . "/sl-webapp";
// set page headers
$page_title = '';
include_once $APP_DIR . "/layouts/header.php";
?>

<div class="d-flex align-items-center justify-content-center section-notfound">
    Den nya användarsidan. Här kan en användare redigera uppgifter om sig själv..
</div>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>