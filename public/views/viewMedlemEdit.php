<?php

$APP_DIR = $viewData['APP_DIR'];
$medlem = $viewData['items'];
$roller = $viewData['roles'];
$page_title = $viewData['title'];

include_once "views/_layouts/header.php";
?>

<form class="border border-primary rounded p-3" action="<?= $viewData['formAction'] ?>" method="POST">
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
        <div class="col-md-4 mb-3">
            <label for="fodelsedatum" class="form-label">Födelsedatum</label>
            <input type="date" class="form-control" id="fodelsedatum" name="fodelsedatum" placeholder="YYYY-MM-DD" value="<?= $medlem->fodelsedatum ?>">
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

    <div class="row">
        <div class="col-md-6 mb-3">
            <h5>Roller</h5>
            <?php foreach ($roller as $roll): ?>
                <div class="form-check form-check-inline">
                    <?php $checked = $medlem->hasRole($roll['id']) ? 'checked' : ''; ?>
                    <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="roller[]" value="<?= $roll['id'] ?>" <?= $checked ?>>
                    <label class="form-check-label" for="inlineCheckbox1"><?= $roll['roll_namn'] ?></label>
                </div>
            <?php endforeach ?>
        </div>
        <div class="col-md-6 mb-3">
            <h5>Andra val</h5>
            <div class="form-check form-check-inline">
                <?php $checked = $medlem->godkant_gdpr ? 'checked' : ''; ?>
                <input class="form-check-input" type="checkbox" id="inlineCheckbox2" name="godkant_gdpr" value="1" <?= $checked ?>>
                <label class="form-check-label" for="inlineCheckbox2">Godkänt GDPR</label>
            </div>
            <div class="form-check form-check-inline">
                <?php $checked = $medlem->pref_kommunikation ? 'checked' : ''; ?>
                <input class="form-check-input" type="checkbox" id="inlineCheckbox3" name="pref_kommunikation" value="1" <?= $checked ?>>
                <label class="form-check-label" for="inlineCheckbox3">Önskar kommunikation</label>
            </div>
            <div class="form-check form-check-inline">
                <?php $checked = $medlem->isAdmin ? 'checked' : ''; ?>
                <input class="form-check-input" type="checkbox" id="inlineCheckbox4" name="isAdmin" value="1" <?= $checked ?>>
                <label class="form-check-label" for="inlineCheckbox4">Admin</label>
            </div>
        </div>
    </div>

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
    <a class="button btn btn-secondary" href="<?php echo $APP_DIR ?>/medlem">Tillbaka</a>
</form>

<!-- Begin the delete form -->
<form class="p-3" action="<?= $viewData['deleteAction'] ?>" method="POST">
    <input type="hidden" name="id" value="<?= $medlem->id; ?>">
    <button type="submit" class="btn btn-danger">Ta bort medlem</button>
</form>

<div class="row rounded px-3">
    <!-- Betalningar -->
    <div class="col-md-6">
        <h3>Betalningar - senaste 5</h3>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Belopp</th>
                        <th>Avser år</th>
                        <th>Kommentar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($viewData['betalningar'], 0, 5) as $betalning) : ?>
                        <tr>
                            <td><?= htmlspecialchars($betalning->datum) ?></td>
                            <td><?= htmlspecialchars($betalning->belopp) ?> kr</td>
                            <td><?= htmlspecialchars($betalning->avser_ar) ?></td>
                            <td><?= htmlspecialchars($betalning->kommentar) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                Lägg till ny betalning
            </button>
            <a href="<?= $viewData['listBetalningAction'] ?>" class="btn btn-secondary">Visa alla</a>
        </div>
    </div>
    <!-- Seglingar -->
    <div class="col-md-6">
        <h3>Seglingar - senaste 5</h3>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Startdatum</th>
                        <th>Roll</th>
                        <th>Skeppslag</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($viewData['seglingar'], 0, 5) as $segling) : ?>
                        <tr>
                            <td><?= htmlspecialchars($segling['startdatum']) ?></td>
                            <td><?= htmlspecialchars($segling['roll_namn']) ?></td>
                            <td>
                                <a href="/segling/<?php echo $segling['segling_id'] ?>"><?= htmlspecialchars($segling['skeppslag']) ?></a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- Modal for adding a new payment -->
<?php // footer
include_once "views/modals/memberBetalningModal.php";
?>

<script>
    // Set default year to current year
    document.addEventListener('DOMContentLoaded', function() {
        const currentYear = new Date().getFullYear();
        document.getElementById('avser_ar').value = currentYear;
    });
</script>

<script>
    function submitPayment() {
        const form = document.getElementById('addPaymentForm');
        const formData = new FormData(form);

        fetch('<?php echo $APP_DIR ?>/betalning/create', { // Adjust this URL to match your routing structure
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('addPaymentModal'));
                    modal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the payment.');
            });
    }
</script>

<?php // footer
include_once "views/_layouts/footer.php";
?>