<?php

$APP_DIR = $viewData['APP_DIR'];
$page_title = $viewData['title'];

include_once "views/_layouts/header.php";
?>
<div class="row col-6 mx-3">
    <table class="table">
        <thead>
            <tr>
                <?php foreach (array_keys(reset($viewData['items'])) as $header):
                    if ($header === "password") {
                        continue;
                    } ?>
                    <th><?= ucfirst($header) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($viewData['items'] as $item) : ?>
                <tr>
                    <?php foreach ($item as $key => $value) :
                        if ($key === "password") {
                            continue;
                        } ?>
                        <td><?= $value ?></td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<?php // footer
include_once "views/_layouts/footer.php";
?>