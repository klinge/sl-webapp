<?php
// set page headers
$page_title = "";
$APP_DIR = $viewData['APP_DIR'];
include_once "views/_layouts/header.php";
?>

<!-- Cards -->
<div class="container mt-3">
    <div class="row">

        <div class="col-sm-4">
            <div class="card border-primary mb-3" style="max-width: 25rem;">
                <div class="card-header">Medlemmar</div>
                <div class="bg-light d-flex justify-content-center" width="100%">
                    <img class="my-1" width="35%" height="35%" src="assets/img/sailor.svg" />
                </div>
                <div class="card-body">
                    <h4 class="card-title">Våra medlemmar</h4>
                    <p class="card-text">Vårt medlemsregister. Här hittar du både medlemmar och nyckelbesättning. </p>
                    <a href="medlem" class="btn btn-primary" tabindex="2" role="button">Se medlemmar..</a>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card border-primary mb-3" style="max-width: 25rem;">
                <div class="card-header">Bokningar</div>
                <div class="bg-light d-flex justify-content-center" width="100%">
                    <img class="mt-2 mb-1" width="50%" height="50%" src="assets/img/steering.svg" />
                </div>
                <div class="card-body">
                    <h4 class="card-title">Bokningslistan</h4>
                    <p class="card-text">Innehåller aktuella bokningar. Här kan du också titta på vilka seglingar som behöver bemannas.</p>
                    <a href="segling" class="btn btn-primary" tabindex="1" role="button">Se bokningar..</a>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card border-primary mb-3" style="max-width: 25rem;">
                <div class="card-header">Rapporter och listor</div>
                <div class="bg-light d-flex justify-content-center" width="100%">
                    <img class="my-1" width="35%" height="35%" src="assets/img/anchor.svg" />
                </div>
                <div class="card-body">
                    <h4 class="card-title">Rapporter</h4>
                    <p class="card-text">Här hittar du olika listor och rapporter du kan ta fram.</p>
                    <a href="reports" class="btn btn-primary" tabindex="3" role="button">Rapporter..</a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php // footer
include_once "views/_layouts/footer.php";
?>