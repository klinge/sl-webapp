<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// include database and object files
include_once 'config/database.php';
include_once 'models/segling.php';

$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];

// set page headers
$page_title = "Seglingar";
include_once $APP_DIR . "/layouts/header.php";

// retrieve records here

// instantiate database and objects
$database = new Database();
$db = $database->getConnection();

$segling = new Segling($db);

// get all members
$result = $segling->getAll();
$num = sizeof($result);
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
    <?php foreach ($result as $row) :
            $segling = new Segling($db, $row['id']);
            $skeppare = array_filter($segling->deltagare, function($member) {
                return $member['roll_namn'] === "Skeppare";
            });
            if($skeppare) {
                $skeppareName = current($skeppare)['fornamn'] . " " . current($skeppare)['efternamn'];
            }
            else {
                $skeppareName = "TBD";
            }
    ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['startdatum'] ?></td>
                <td><?= $row['slutdatum'] ?></td>
                <td>TODO</td>
                <td><?= $row['skeppslag'] ?></td>
                <td><?= $row['kommentar'] ?></td>
                <td><?= $skeppareName ?></td>
                <td>TODO</td>
                <td>TODO</td>
                <td>TODO</td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm edit-btn" data-bs-toggle="modal" data-bs-target="#editSeglingModal" data-segling-id="<?= $row['id'] ?>">Ändra</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php //include modal
    include_once $APP_DIR . "/views/viewSeglingModal.php"; 
?>
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