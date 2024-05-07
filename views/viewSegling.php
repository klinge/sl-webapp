<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = $data['title'];
include_once $APP_DIR . "/layouts/header.php";

$num = sizeof($data['items']);
?>

<table class='table table-hover table-responsive table-bordered table-striped' id="sailingTable">
    <thead>
        <tr>
            <th>Id</th>
            <th>Start</th>
            <th>Slut</th>
            <th>Dagar</th>
            <th>Skeppslag</th>
            <th>Kommentar</th>
            <th>Skeppare</th>
            <th>Båtsman</th>
            <th>Kock</th>
            <th>X-Kock</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($result as $row) : ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['startdatum'] ?></td>
                <td><?= $row['slutdatum'] ?></td>
                <td>TODO</td>
                <td><?= $row['skeppslag'] ?></td>
                <td><?= $row['kommentar'] ?></td>
                <td>TODO</td>
                <td>TODO</td>
                <td>TODO</td>
                <td>TODO</td>
                <td>
                <a type="button" class="btn btn-primary btn-sm edit-segling-btn" href="segling/<?= $row['id'] ?>">Ändra</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="assets/js/site.js"></script>

<!-- datatables js -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.0.5/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script>
    let dataTable = new DataTable('#sailingTable');
</script>

<?php
// footer
include_once $APP_DIR . "/layouts/footer.php";
?>