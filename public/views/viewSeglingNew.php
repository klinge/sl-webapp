<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = $viewData['title'];

include_once "views/_layouts/header.php";
?>

<div class="container">
    <form class="border border-primary rounded p-3" action="<?= $viewData['formUrl'] ?>" method="POST">
        <input type="hidden" name="Content-Type" value="application/x-www-form-urlencoded">

        <div class="row">
            <div class="col-md-2 mb-3">
                <label for="startdat" class="form-label">Startdatum</label>
                <input type="date" class="form-control" id="startdat" name="startdat" placeholder="Ange startdatum" required>
            </div>
            <div class="col-md-2 mb-3">
                <label for="slutdat" class="form-label">Slutdatum</label>
                <input type="date" class="form-control" id="slutdat" name="slutdat" placeholder="Ange slutdatum" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="skeppslag" class="form-label">Skeppslag</label>
                <input type="text" class="form-control" id="skeppslag" name="skeppslag" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="kommentar" class="form-label">Kommentar</label>
                <textarea class="form-control" id="kommentar" name="kommentar"></textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Spara</button>
        <a class="button btn btn-secondary" href="<?php echo $APP_DIR ?>/segling">Tillbaka</a>
    </form>
</div>

<?php // footer
include_once "views/_layouts/footer.php";
?>