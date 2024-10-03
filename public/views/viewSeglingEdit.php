<?php

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = $viewData['title'];
include_once "views/_layouts/header.php";

$segling = $viewData['items'];
$roller = $viewData['roles'];
?>

<div class="container">
    <form class="border border-primary rounded p-3" action="<?= $viewData['formUrl'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $viewData["csrf_token"]; ?>">
        <input type="hidden" name="Content-Type" value="application/x-www-form-urlencoded">

        <div class="row">
            <div class="col-md-2 mb-3">
                <label for="startdat" class="form-label">Startdatum</label>
                <input type="text" class="form-control" id="startdat" name="startdat" placeholder="Ange startdatum" value="<?= $segling->start_dat ?>">
            </div>
            <div class="col-md-2 mb-3">
                <label for="slutdat" class="form-label">Slutdatum</label>
                <input type="text" class="form-control" id="slutdat" name="slutdat" placeholder="Ange slutdatum" value="<?= $segling->slut_dat ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label for="skeppslag" class="form-label">Skeppslag</label>
                <input type="text" class="form-control" id="skeppslag" name="skeppslag" value="<?= $segling->skeppslag ?>">
            </div>
        </div>

        <div class="row">

            <div class="col-md-5 table-responsive">
                <h5>Nyckelbesättning</h5>
                <table id="crewTable" class="table table-sm table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Roll</th>
                            <th>Namn</th>
                            <th>Medlemsavg</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segling->deltagare as $deltagare) : ?>
                            <?php if (isset($deltagare['roll_namn'])) : ?>
                                <tr>
                                    <td><?= $deltagare['roll_namn'] ?></td>
                                    <td>
                                        <a class="link-primary" href="<?php echo $APP_DIR ?>/medlem/<?php echo $deltagare['medlem_id'] ?>">
                                            <?= $deltagare['fornamn'] ?> <?= $deltagare['efternamn'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($deltagare['har_betalt']) : ?>
                                            <button class="btn btn-sm btn-success">
                                                <i class="fs-5 bi bi-file-check"></i>
                                            </button>
                                        <?php else : ?>
                                            <button class="btn btn-sm btn-warning">
                                                <i class="fs-5 bi bi-file-x"></i>
                                            </button>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary delete-medlem"
                                            data-segling-id="<?= $segling->id ?>" data-medlem-id="<?= $deltagare['medlem_id'] ?>"
                                            title="Ta bort">
                                            <i class="fs-5 bi bi-x-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif ?>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-4 table-responsive">
                <h5>Övriga seglande medlemmar</h5>
                <table id="participantsTable" class="table table-sm table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Namn</th>
                            <th>Medlemsavg</th>
                            <th>Åtgärder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($segling->deltagare as $deltagare) : ?>
                            <?php if (empty($deltagare['roll_namn'])) : ?>
                                <tr>
                                    <td>
                                        <a class="link-primary" href="<?php echo $APP_DIR ?>/medlem/<?php echo $deltagare['medlem_id'] ?>">
                                            <?= $deltagare['fornamn'] ?> <?= $deltagare['efternamn'] ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($deltagare['har_betalt']) : ?>
                                            <button class="btn btn-sm btn-success">
                                                <i class="fs-5 bi bi-file-check"></i>
                                            </button>
                                        <?php else : ?>
                                            <button class="btn btn-sm btn-warning">
                                                <i class="fs-5 bi bi-file-x"></i>
                                            </button>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary delete-medlem"
                                            data-segling-id="<?= $segling->id ?>" data-medlem-id="<?= $deltagare['medlem_id'] ?>"
                                            title="Ta bort">
                                            <i class="fs-5 bi bi-x-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endif ?>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2 mb-3">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    Lägg till deltagare
                </button>
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
        <a class="button btn btn-warning" onclick="deleteSegling(<?php echo $segling->id ?>)">Ta bort</a>
        <a class="button btn btn-secondary" href="<?php echo $APP_DIR ?>/segling">Tillbaka</a>
    </form>
</div>

<script>
    document.querySelectorAll('.delete-medlem').forEach(button => {
        button.addEventListener('click', function() {
            const seglingId = this.dataset.seglingId;
            const medlemId = this.dataset.medlemId;

            if (confirm('Är du säker på att du vill ta bort denna deltagare från seglingen?')) {
                fetch('<?php echo $APP_DIR ?>/segling/medlem/delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            segling_id: seglingId,
                            medlem_id: medlemId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table or refresh the participant list
                            this.closest('tr').remove();
                        } else {
                            alert('Ett fel uppstod vid borttagning av deltagaren.');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    });

    function deleteSegling(seglingId) {
        if (confirm('Are you sure you want to delete this segling?')) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo $APP_DIR ?>/segling/delete/' + seglingId, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                window.location = '<?php echo $APP_DIR ?>/segling';
            };

            var csrfToken = '<?php echo $viewData["csrf_token"]; ?>';
            xhr.send('csrf_token=' + encodeURIComponent(csrfToken));
        }
    }
</script>

<?php // footer
include_once "views/modals/seglingAddMedlemModal.php";
include_once "views/_layouts/footer.php";
?>