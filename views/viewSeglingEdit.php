<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = $data['title'];
include_once $APP_DIR . "/layouts/header.php";

$segling = $data['items'];
$roller = $data['roles'];
?>

<div class="container">
    <form class="border border-primary rounded p-3" action="<?= $formAction ?>" method="POST">
        <input type="hidden" name="Content-Type" value="application/x-www-form-urlencoded">
        
        <div class="row">
            <div class="col-md-2 mb-3">
                <label for="startdat" class="form-label">Startdatum</label>
                <input type="text" class="form-control" id="startdat" name="startdat" placeholder="Ange förnamn" value="<?= $segling->start_dat ?>">
            </div>
            <div class="col-md-2 mb-3">
                <label for="slutdat" class="form-label">Slutdatum</label>
                <input type="text" class="form-control" id="slutdat" name="slutdat" placeholder="Ange efternamn" value="<?= $segling->slut_dat ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="skeppslag" class="form-label">Skeppslag</label>
                <input type="text" class="form-control" id="skeppslag" name="skeppslag" value="<?= $segling->skeppslag ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <p>Bemanning</p>
                <table class="table table-striped">
                <?php foreach($segling->deltagare as $deltagare) : ?>
                    <tr>
                        <td><?= $deltagare['roll_namn'] ?></td>
                        <td><?= $deltagare['fornamn'] ?> <?= $deltagare['efternamn'] ?></td>
                    </tr>
                <?php endforeach ?>
                </table>
            </div>

        </div>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="kommentar" class="form-label">Kommentar</label>
                <textarea class="form-control" id="kommentar" name="kommentar"><?= $segling->kommentar ?></textarea>
            </div>
        </div>

        <div class="row">
            <div class="d-inline-flex col-md-3 mb-3">
                <label for="created" class="form-label form-control-sm">Skapad:</label>
                <span class="form-control-plaintext form-control-sm" id="created"><?= $segling->created_at ?></span>
            </div>
            <div class="d-inline-flex col-md-3 mb-3">
                <label for="updated" class="form-label form-control-sm">Ändrad:</label>
                <span class="form-control-plaintext form-control-sm" id="updated"><?= $segling->updated_at ?></span>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Uppdatera</button>
        <a class="button btn btn-secondary" href="/sl-webapp/medlem">Tillbaka</a>
    </form>
</div>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>