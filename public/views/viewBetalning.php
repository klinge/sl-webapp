<?php

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = $data['title'];
include_once "views/_layouts/header.php";

if (isset($data['items'])) {
    $num = sizeof($viewData['items']);
    $data = $viewData['items'];
} else {
    $num = 0;
    $result = [];
}
?>

<table class='table table-hover table-responsive table-bordered table-striped' id="betalningTable">
    <thead>
        <tr>
            <th>Id</th>
            <th>Medlemsnr</th>
            <th>Namn</th>
            <th>Belopp</th>
            <th>Datum</th>
            <th>Avser år</th>
            <th>Kommentar</th>
            <th>Skapad</th>
            <th>Uppdaterad</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $betalning): ?>
            <tr>
                <td><?= $betalning['id'] ?></td>
                <td><?= $betalning['medlem_id'] ?></td>
                <td><?= $betalning['fornamn'] . " " . $betalning['efternamn'] ?></td>
                <td><?= $betalning['belopp'] ?></td>
                <td><?= $betalning['datum'] ?></td>
                <td><?= $betalning['avser_ar'] ?></td>
                <td><?= $betalning['kommentar'] ?></td>
                <td><?= $betalning['created_at'] ?></td>
                <td><?= $betalning['updated_at'] ?></td>
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
    let dataTable = new DataTable('#betalningTable');
    document.querySelector('#betalningTable').addEventListener('click', function(e) {
        // Get the parent row of the clicked cell
        let row = dataTable.row(e.target.closest('tr'));
        let rowData = row.data();
        let id = rowData[0]; // First column contains ID

        window.location.href = `/betalning/medlem/${id}`;
    });
</script>

<?php // footer
include_once "views/_layouts/footer.php";
?>