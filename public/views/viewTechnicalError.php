<?php
$APP_DIR = $viewData['APP_DIR'];
// set page headers
$page_title = '';
include_once "views/_layouts/header.php";
?>

<div class="d-flex align-items-center justify-content-center section-notfound">
    <div class="text-center">
        <p class="fs-3">
            <img src="assets/img/errors/tech-error-768x493.png" class="img-fluid" alt="image">
        </p>
        <h1 class="display-1 fw-bold"><span class="text-danger">Oops!</span> Nu blev något fel.. </h1>
        <p class="lead">
            Ett tekniskt fel inträffade. Försök igen eller kontakta oss på <a mailto:"info@sofialinnea.se">info@sofialinnea.se</a>.
        </p>
        <a href="/" class="btn btn-primary">Tillbaka till hemsidan.</a>
    </div>
</div>

<?php // footer
include_once "views/_layouts/footer.php";
?>