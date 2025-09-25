<?php

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = $viewData['title'];
include_once "views/_layouts/header.php";

$num = sizeof($viewData['items']);
?>

<div class="d-flex justify-content-end">
    <a href="<?= $viewData['newAction'] ?>" class="btn btn-primary btn-lg" alt="Lägg till medlem">
        Ny medlem
    </a>
</div>

<div class="row">
    <div class="col-md-12">
        <table class='table table-striped table-hover table-bordered' data-page-length="15" id="memberTable">
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($viewData['items'] as $medlem): ?>
                    <tr>
                        <td><?= $medlem->id ?></td>
                        <td><?= $medlem->fornamn ?></td>
                        <td><?= $medlem->efternamn ?></td>
                        <td><?php $roll_namn_array = array_column($medlem->roller, 'roll_namn');
                            echo implode(', ', $roll_namn_array); ?></td>
                        <td><?= $medlem->email ?></td>
                        <td><?= $medlem->mobil ?></td>
                        <td><?= $medlem->telefon ?></td>
                        <td><?= $medlem->adress ?></td>
                        <td><?= $medlem->postnummer ?></td>
                        <td><?= $medlem->postort ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

<!-- datatables js -->
<!-- get download package from https://datatables.net/download/ -->
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" 
    integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-2.3.4/r-3.0.6/datatables.min.js" 
    integrity="sha384-eneEmqnwUnl1RFOvdIX8DEhLr2BQi6rEMRzdVfE1THcqs0MlPQf+J+4uHLphmE2v" crossorigin="anonymous"></script>
</script>
<script>
    let dataTable = new DataTable(' #memberTable', {
        ordering: true, // Enable sorting
        order: [
            [2, 'asc']
        ],
        "language": {
            "lengthMenu": "Visa _MENU_ rader",
            "info": "Visar _START_ till _END_ av totalt _TOTAL_ poster",
            "infoEmpty": "Visar 0 av totalt 0 poster",
            "loadingRecords": "Laddar...",
            "search": "Sök:",
            "zeroRecords": "Inga poster hittades",
            "paginate": {
                "first": "<<",
                "last": ">>",
                "next": ">",
                "previous": "<"
            },
        }
    });
    document.querySelector('#memberTable').addEventListener('click', function(e) {
        // Get the parent row of the clicked cell
        let row = dataTable.row(e.target.closest('tr'));
        let rowData = row.data();
        let id = rowData[0]; // First column contains ID

        window.location.href = `/medlem/${id}`;
    });
</script>

<?php // footer
include_once "views/_layouts/footer.php";
?>