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

<table class='table table-hover table-responsive table-bordered table-striped' id="memberTable">
    <thead>
        <tr>
            <th>Id</th>
            <th>Förnamn</th>
            <th>Eftermamn</th>
            <th>Roll</th>
            <th>Email</th>
            <th>Mobil</th>
            <th>Telefon</th>
            <th>Adress</th>
            <th>Postnr</th>
            <th>Ort</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($result as $medlem) :
            $thisMember = new Medlem($db, $medlem['id']);
            $rollLista = "";
            foreach ($thisMember->roller as $roll) {
                $rollLista .= " " . $roll["roll_namn"];
            }
        ?>
            <tr>
                <td><?= $medlem['id'] ?></td>
                <td><?= $medlem['fornamn'] ?></td>
                <td><?= $medlem['efternamn'] ?></td>
                <td><?= $rollLista ?></td>
                <td><?= $medlem['email'] ?></td>
                <td><?= $medlem['mobil'] ?></td>
                <td><?= $medlem['telefon'] ?></td>
                <td><?= $medlem['gatuadress'] ?></td>
                <td><?= $medlem['postnummer'] ?></td>
                <td><?= $medlem['postort'] ?></td>
                <td>
                    <a type="button" class="btn btn-primary btn-sm edit-member-btn" href="medlem/<?= $medlem['id'] ?>">Ändra</button>
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
    let dataTable = new DataTable('#memberTable');
</script>

<?php // footer
    include_once $APP_DIR . "/layouts/footer.php";
?>