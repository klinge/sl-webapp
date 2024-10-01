<?php

$APP_DIR = $viewData['APP_DIR'];

// set page headers
$page_title = $data['title'];
include_once "views/_layouts/header.php";

if (isset($data['items'])) {
    $num = sizeof($viewData['items']);
    $result = $viewData['items'];
} else {
    $num = 0;
    $result = [];
}
?>

<table class='table table-hover table-responsive table-bordered table-striped' id="betalningTable">
    <thead>
        <tr>
            <th>Id</th>
            <th>Betalare</th>
            <th>Belopp</th>
            <th>Datum</th>
            <th>Avser år</th>
            <th>Kommentar</th>
            <th>Skapad</th>
            <th>Uppdaterad</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($result as $betalning): ?>
            <tr>
                <td><?= $betalning->id ?></td>
                <td><?= $betalning->medlem_id ?></td>
                <td><?= $betalning->belopp ?></td>
                <td><?= $betalning->datum ?></td>
                <td><?= $betalning->avser_ar ?></td>
                <td><?= $betalning->kommentar ?></td>
                <td><?= $betalning->created_at ?></td>
                <td><?= $betalning->updated_at ?></td>
                <td>
                    <a type="button" class="btn btn-primary btn-sm edit-member-btn" href="betalning/<?= $betalning->id ?>">Ändra</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="assets/js/site.js"></script>

<!-- datatables js -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3/dist/jquery.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/dt-2.1.7/r-3.0.3/datatables.min.js"
    integrity="sha256-xRNRfHSAzfeyNtcHElIWRe+lWt+vVVct91efkO7VR9c=" crossorigin="anonymous">
</script>
<script>
    let dataTable = new DataTable('#betalningTable');
</script>

<?php // footer
include_once "views/_layouts/footer.php";
?>