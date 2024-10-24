<?php

declare(strict_types=1);

use App\Utils\CsvImporter;

$importer = new CsvImporter('sldb-prod.sqlite');
//$result = $importer->findMembersInCsv('B24', 'SM');
//print_r($result);

$importer->deleteMedlemmar();
$updatedRows = $importer->insertToDb();
echo "--- Rader som inte gick att läsa in från csv ---" . PHP_EOL;
print_r($importer->csvRowsNotImported);
echo "--- Rader som inte gick att skapa i databasen ---" . PHP_EOL;
print_r($importer->dbRowsNotCreated);
$importer->addJohanWithPwdAndAdmin();
