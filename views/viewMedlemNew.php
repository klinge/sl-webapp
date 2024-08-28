<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = $data['title'];
include_once $APP_DIR . "/layouts/header.php";
?>

<div class="container">
    <form class="border border-primary rounded p-3" action="<?= $data['formAction'] ?>" method="POST">
        <input type="hidden" name="Content-Type" value="application/x-www-form-urlencoded">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="fornamn" class="form-label">Förnamn</label>
                <input type="text" class="form-control" id="fornamn" name="fornamn" placeholder="Ange förnamn">
            </div>
            <div class="col-md-4 mb-3">
                <label for="efternamn" class="form-label">Efternamn</label>
                <input type="text" class="form-control" id="efternamn" name="efternamn" placeholder="Ange efternamn">
            </div>
            <div class="col-md-4 mb-3">
                <label for="fodelsedatum" class="form-label">Födelsedatum</label>
                <input type="date" class="form-control" id="fodelsedatum" name="fodelsedatum" placeholder="YYYY-MM-DD">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="col-md-4 mb-3">
                <label for="mobil" class="form-label">Mobil</label>
                <input type="text" class="form-control" id="mobil" name="mobil" placeholder="Mobilnummer">
            </div>
            <div class="col-md-4 mb-3">
                <label for="telefon" class="form-label">Telefon</label>
                <input type="text" class="form-control" id="telefon" name="telefon" placeholder="Annat telefonnummer">
            </div>
        </div>


        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="adress" class="form-label">Adress</label>
                <input type="text" class="form-control" id="adress" name="adress" placeholder="Ange gatuadress">
            </div>
            <div class="col-md-4 mb-3">
                <label for="postnr" class="form-label">Postnummer</label>
                <input type="text" class="form-control" id="postnr" name="postnummer" placeholder="Ange postnummer">
            </div>
            <div class="col-md-4 mb-3">
                <label for="ort" class="form-label">Ort</label>
                <input type="text" class="form-control" id="ort" name="postort" placeholder="Ange ort">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <h5>Roller</h5>
                <?php foreach ($roller as $roll): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="roller[]" value="<?= $roll['id'] ?>">
                        <label class="form-check-label" for="inlineCheckbox1"><?= $roll['roll_namn'] ?></label>
                    </div>
                <?php endforeach ?>
            </div>
            <div class="col-md-6 mb-3">
                <h5>Andra val</h5>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="inlineCheckbox2" name="godkant_gdpr" value="1">
                    <label class="form-check-label" for="inlineCheckbox2">Godkänt GDPR</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="inlineCheckbox3" name="pref_kommunikation" value="1" checked>
                    <label class="form-check-label" for="inlineCheckbox3">Önskar kommunikation</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="inlineCheckbox4" name="isAdmin" value="1">
                    <label class="form-check-label" for="inlineCheckbox4">Admin</label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="kommentar" class="form-label">Kommentar</label>
                <textarea class="form-control" id="kommentar" name="kommentar"></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Spara</button>
        <a class="button btn btn-secondary" href="/sl-webapp/medlem">Tillbaka</a>
    </form>