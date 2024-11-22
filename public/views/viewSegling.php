<?php

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = $viewData['title'];
include_once "views/_layouts/header.php";

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
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- datatables js -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.1.7/r-3.0.3/datatables.min.js"
    integrity="sha256-xRNRfHSAzfeyNtcHElIWRe+lWt+vVVct91efkO7VR9c=" crossorigin="anonymous">
</script>
<script>
    let dataTable = new DataTable('#sailingTable', {
        responsive: true
    });
    document.querySelector('#sailingTable').addEventListener('click', function(e) {
        // Get the parent row of the clicked cell
        let row = dataTable.row(e.target.closest('tr'));
        let rowData = row.data();
        let id = rowData[0]; // First column contains ID

        window.location.href = `/segling/${id}`;
    });
</script>

<?php
// footer
include_once "views/_layouts/footer.php";
?>