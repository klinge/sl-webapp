<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = $data['title'];
include_once $APP_DIR . "/layouts/header.php";

$medlem = $data['items'];
$roller = $data['roles'];
?>

<div class="container">
    <form class="border border-primary rounded p-3" action="<?= $formAction ?>" method="POST">
        <input type="hidden" name="Content-Type" value="application/x-www-form-urlencoded">
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="fornamn" class="form-label">Förnamn</label>
                <input type="text" class="form-control" id="fornamn" name="fornamn" placeholder="Ange förnamn" value="<?= $medlem->fornamn ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="efternamn" class="form-label">Efternamn</label>
                <input type="text" class="form-control" id="efternamn" name="efternamn" placeholder="Ange efternamn" value="<?= $medlem->efternamn ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $medlem->email ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="mobil" class="form-label">Mobil</label>
                <input type="text" class="form-control" id="mobil" name="mobil" placeholder="Mobilnummer" value="<?= $medlem->mobil ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="telefon" class="form-label">Telefon</label>
                <input type="text" class="form-control" id="telefon" name="telefon" placeholder="Annat telefonnummer" value="<?= $medlem->telefon ?>">
            </div>
        </div>


        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="adress" class="form-label">Adress</label>
                <input type="text" class="form-control" id="adress" name="adress" placeholder="Ange gatuadress" value="<?= $medlem->adress ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="postnr" class="form-label">Postnummer</label>
                <input type="text" class="form-control" id="postnr" name="postnummer" placeholder="Ange postnummer" value="<?= $medlem->postnummer ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="ort" class="form-label">Ort</label>
                <input type="text" class="form-control" id="ort" name="postort" placeholder="Ange ort" value="<?= $medlem->postort ?>">
            </div>
        </div>

        <h5>Roller</h5>
        <?php foreach ($roller as $roll): ?>
            <div class="form-check form-check-inline">
                <?php $checked = $medlem->hasRole($roll['id']) ? 'checked' : ''; ?>
                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="roller[]" value="<?= $roll['id'] ?>" <?= $checked ?> >
                <label class="form-check-label" for="inlineCheckbox1"><?= $roll['roll_namn'] ?></label>
            </div>
        <?php endforeach ?>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="kommentar" class="form-label">Kommentar</label>
                <textarea class="form-control" id="kommentar" name="kommentar"><?= $medlem->kommentar ?></textarea>
            </div>
        </div>

        <div class="row">
            <div class="d-inline-flex col-md-3 mb-3">
                <label for="created" class="form-label form-control-sm">Skapad:</label>
                <span class="form-control-plaintext form-control-sm" id="created"><?= $medlem->created_at ?></span>
            </div>
            <div class="d-inline-flex col-md-3 mb-3">
                <label for="updated" class="form-label form-control-sm">Ändrad:</label>
                <span class="form-control-plaintext form-control-sm" id="updated"><?= $medlem->updated_at ?></span>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Uppdatera</button>
        <a class="button btn btn-secondary" href="/sl-webapp/medlem">Tillbaka</a>
    </form>

    <div class="border border-primary rounded p-3 mt-2" style="background-color: var(--bs-gray-200);">
        <h3>Senaste seglingarna:</h3>
        <ul>
            <?php foreach($data['seglingar'] as $segling) : ?>
                <li><?= $segling['startdatum'] ?> <?= $segling['roll_namn'] ?>, Skeppslag: <?= $segling['skeppslag'] ?></li>
            <?php endforeach ?>
        </ul>
    </div>
</div>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>