<?php
$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];
// set page headers
$page_title = "";
include_once $APP_DIR . "/layouts/header.php";
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
          <a href="/sl-webapp/medlem" class="btn btn-primary" tabindex="2" role="button">Se medlemmar..</a>
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
          <a href="/sl-webapp/segling" class="btn btn-primary" tabindex="1" role="button">Se bokningar..</a>
        </div>
      </div>
    </div>

    <div class="col-sm-4">
      <div class="card border-primary mb-3" style="max-width: 25rem;">
        <div class="card-header">Rapporter</div>
        <div class="bg-light d-flex justify-content-center" width="100%">
          <img class="my-1" width="35%" height="35%" src="assets/img/anchor.svg" />
        </div>
        <div class="card-body">
          <h4 class="card-title">Alla rapporter</h4>
          <p class="card-text">Inte implementerad än.. Här hittar du olika rapporter du kan ta fram.</p>
          <a href="sl_skeppslag.php" class="btn btn-primary" tabindex="3" role="button">Se skeppslag..</a>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>