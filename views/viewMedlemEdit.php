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

//Function to check if a member has a given role, used for the role checkboxes
function hasRole($role, $arrayOfRoles) {
    foreach ($arrayOfRoles as $innerArray) {
      if (isset($innerArray['roll_id']) && $innerArray['roll_id'] === $role) {
        return true;
      }
    }
    return false;
  }
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
                <?php $checked = hasRole($roll['id'], $medlem->roller) ? 'checked' : ''; ?>
                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="roller[]" value="<?= $roll['id'] ?>" <?= $checked ?> >
                <label class="form-check-label" for="inlineCheckbox1"><?= $roll['roll_namn'] ?></label>
            </div>
        <?php endforeach ?>

        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="kommentar" class="form-label">Kommentar</label>
                <input type="text" class="form-control" id="kommentar" name="kommentar" value="<?= $medlem->kommentar ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Uppdatera</button>
        <a class="button btn btn-secondary" href="/sl-webapp/medlem">Tillbaka</a>
    </form>
</div>


<!-- datatables js -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script>
    let dataTable = new DataTable('#memberTable');
</script>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>