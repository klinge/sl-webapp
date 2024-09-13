<?php
$page_title = "";
$APP_DIR = $_SERVER['DOCUMENT_ROOT'] . "/sl-webapp";
// set page headers
$page_title = '';
include_once $APP_DIR . "/layouts/header.php";
?>

<div class="d-flex align-items-center justify-content-center section-notfound">
    <div class="text-center">
        <p class="fs-3">
            <img src="assets/img/errors/404.png" class="img-fluid" alt="image">
        </p>
        <h1 class="display-1 fw-bold">404</h1>
        <p class="fs-3"> <span class="text-danger">Oops!</span> Page not found</p>
        <p class="lead">
            Sidan du försökte nå finns inte.
        </p>
        <a href="/sl-webapp" class="btn btn-primary">Tillbaka till hemsidan.</a>
    </div>
</div>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>