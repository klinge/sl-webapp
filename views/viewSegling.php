<?php
// enable debug info
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = $viewData['title'];
include_once $APP_DIR . "/layouts/header.php";

//Seglingar is an array of Segling objects
$seglingar = $viewData['items'];
$num = sizeof($seglingar);

?>

<div class="d-flex justify-content-end">
    <a href="<?= $viewData['newAction'] ?>" class="btn btn-primary btn-lg" alt="Lägg till medlem">
        Ny segling
    </a>
</div>

<table class='table table-bordered table-striped table-hover' id="sailingTable">
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
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($seglingar as $segling) :
            //fetch who has what role
            $skeppare = $segling->getDeltagareByRoleName("Skeppare");
            $skepparNamn = $skeppare ? $skeppare[0]['fornamn'] . " " . $skeppare[0]['efternamn'] : "TBD";
            $batsman = $segling->getDeltagareByRoleName("Båtsman");
            $batsmanNamn = $batsman ? $batsman[0]['fornamn'] . " " . $batsman[0]['efternamn'] : "TBD";
            $kock = $segling->getDeltagareByRoleName("Kock");
            $kockNamn = $kock ? $kock[0]['fornamn'] . " " . $kock[0]['efternamn'] : "TBD";
        ?>
            <tr>
                <td><?= $segling->id ?></td>
                <td><?= $segling->start_dat ?></td>
                <td><?= $segling->slut_dat ?></td>
                <td>TODO</td>
                <td><?= $segling->skeppslag ?></td>
                <td><?= $segling->kommentar ?></td>
                <td><?= $skepparNamn ?></td>
                <td><?= $batsmanNamn ?></td>
                <td><?= $kockNamn ?></td>
                <td>
                    <a type="button" class="btn btn-primary btn-sm edit-segling-btn" href="./segling/<?= $segling->id ?>">Ändra</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="assets/js/site.js"></script>

<!-- datatables js -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.1.5/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script>
    let dataTable = new DataTable('#sailingTable', {
        responsive: true
    });
</script>

<?php
// footer
include_once $APP_DIR . "/layouts/footer.php";
?>