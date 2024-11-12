<?php

$APP_DIR = $viewData['APP_DIR'];
$page_title = $viewData['title'];

include_once "views/_layouts/header.php";
?>
<div class="container">
    <div class="row">

        <div class="col-sm-4">
            <div class="card">
                <img class="card-img-top" src="/assets/img/reports/betalning-600x400.png" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title">Betalning</h5>
                    <p class="card-text">Visa medlemmar som inte betalat:</p>
                    <form action="/reports/payments" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $viewData["csrf_token"]; ?>">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="yearRadio" id="year1" value="1" checked>
                            <label class="form-check-label" for="year1">
                                Innevarande år
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="yearRadio" id="year2" value="2">
                            <label class="form-check-label" for="year2">
                                Innevarande och föregående år
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="yearRadio" id="year3" value="3">
                            <label class="form-check-label" for="year3">
                                Innevarande och två föregående åren
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3" type="submit">Visa</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card">
                <img class="card-img-top" src="/assets/img/reports/email-600x400.png" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title">Mailadresser</h5>
                    <p class="card-text">Visa mailadresser för aktiva medlemmar. Utesluter medlemmar som valt att inte få
                        kommunikation från oss, samt medlemmar som inte betalt under innevarande eller föregående år.
                    </p>
                    <a href="/reports/member-emails" class="btn btn-primary">Visa</a>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card">
                <img class="card-img-top" src="https://placehold.co/600x400" alt="Card image cap">
                <div class="card-body">
                    <h5 class="card-title">Nåt annat</h5>
                    <p class="card-text">Nån annan rapport</p>
                    <a href="#" class="btn btn-primary">Go somewhere</a>
                </div>
            </div>
        </div>


    </div>
</div>

<?php // footer
include_once "views/_layouts/footer.php";
?>