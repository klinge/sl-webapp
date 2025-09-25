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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" 
    integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.3.4/r-3.0.6/datatables.min.js" 
    integrity="sha384-eneEmqnwUnl1RFOvdIX8DEhLr2BQi6rEMRzdVfE1THcqs0MlPQf+J+4uHLphmE2v" crossorigin="anonymous"></script>
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