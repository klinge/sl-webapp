<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = $viewData['title'];
include_once $APP_DIR . "/layouts/header.php";

$num = sizeof($viewData['items']);

?>

<div class="d-flex justify-content-end">
    <a href="<?= $viewData['newAction'] ?>" class="btn btn-primary btn-lg" alt="Lägg till medlem">
        Ny medlem
    </a>
</div>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <table class='table display dt-responsive nowrap table-hover table-bordered table-striped' id="memberTable">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Förnamn</th>
                        <th>Efternamn</th>
                        <th>Roll</th>
                        <th>Email</th>
                        <th>Mobil</th>
                        <th data-priority="1">Telefon</th>
                        <th data-priority="4">Adress</th>
                        <th data-priority="3">Postnr</th>
                        <th data-priority="2">Ort</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($viewData['items'] as $medlem): ?>
                        <tr>
                            <td><?= $medlem->id ?></td>
                            <td><?= $medlem->fornamn ?></td>
                            <td><?= $medlem->efternamn ?></td>
                            <td><?php $roll_namn_array = array_column($medlem->roller, 'roll_namn');
                                echo (implode(', ', $roll_namn_array)); ?></td>
                            <td><?= $medlem->email ?></td>
                            <td><?= $medlem->mobil ?></td>
                            <td><?= $medlem->telefon ?></td>
                            <td><?= $medlem->adress ?></td>
                            <td><?= $medlem->postnummer ?></td>
                            <td><?= $medlem->postort ?></td>
                            <td>
                                <a type="button" class="btn btn-primary btn-sm edit-member-btn" href="medlem/<?= $medlem->id ?>">Ändra</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<script src="assets/js/site.js"></script>

<!-- datatables js -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script>
    let dataTable = new DataTable('#memberTable', {
        ordering: true, // Enable sorting
        order: [
            [2, 'asc']
        ] // Sort on the third column (index 2) in ascending order by default
    });
</script>

<?php // footer
include_once $APP_DIR . "/layouts/footer.php";
?>