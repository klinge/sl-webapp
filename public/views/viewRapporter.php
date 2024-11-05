<?php

$APP_DIR = $viewData['APP_DIR'];
$page_title = $viewData['title'];

include_once "views/_layouts/header.php";
?>
<div class="container">
    <div class="row">

        <div class="col-sm-4">
            <div class="card">
                <img class="card-img-top" src="https://placehold.co/600x400" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title">Betalning</h5>
                    <p class="card-text">Visa medlemmar som inte betalat:</p>
                    <form action="/reports/payments" method="POST">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pmtRadio" id="thisyear" value="1" checked>
                            <label class="form-check-label" for="exampleRadios1">
                                i år
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pmrRadio" id="thisandlastyear" value="2">
                            <label class="form-check-label" for="exampleRadios2">
                                inte två senaste åren
                            </label>
                        </div>
                        <submit class="btn btn-primary mt-3" type="submit">Visa</submit>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card">
                <img class="card-img-top" src="https://placehold.co/600x400" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title">Mailadresser</h5>
                    <p class="card-text">Visa mailadresser för aktiva medlemmar</p>
                    <a href="#" class="btn btn-primary">Go somewhere</a>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card">
                <img class="card-img-top" src="https://placehold.co/600x400" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title">Mailadresser</h5>
                    <p class="card-text">Visa mailadresser för aktiva medlemmar</p>
                    <a href="#" class="btn btn-primary">Go somewhere</a>
                </div>
            </div>
        </div>


    </div>
</div>

<?php // footer
include_once "views/_layouts/footer.php";
?>