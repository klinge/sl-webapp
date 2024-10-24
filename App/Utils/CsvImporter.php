<?php

declare(strict_types=1);

namespace App\Utils;

use PDO;
use PDOException;
use App\Application;
use App\Models\Medlem;
use App\Utils\DateFormatter;
use InvalidArgumentException;

/**
 * @deprecated This class will is just for initial database import and will be deprecated in upcoming releases.
 */
class CsvImporter
{
    public array $data = [];
    private PDO $conn;
    private string $dbfile;
    private string $csvfile;
    public array $csvRowsNotImported = [];
    public array $dbRowsNotCreated = [];
    public Application $app;

    public function __construct(string $dbFilename)
    {
        $this->app = new Application();
        $this->dbfile = $this->app->getRootDir() . '/db/' . $dbFilename;
        $this->csvfile = $this->app->getRootDir() . '/db/csv-data/medlemmar-cleaned.csv';

        $this->data = $this->readCsv();

        try {
            $this->connect();
        } catch (PDOException $e) {
            error_log("Fel vid anslutning till databas", 0);
            exit;
        } catch (InvalidArgumentException $e) {
            error_log("Fel: " . $e->getMessage());
            exit;
        }
    }

    public function findMembersInCsv(string $key, string $searchValue): array
    {
        // Print some things to see if import went well
        $foundMembers = array_filter($this->data, function ($member) use ($key, $searchValue) {
            if ($searchValue === "*" || $searchValue === "") {
                return isset($member[$key]);
            } else {
                return isset($member[$key]) && $member[$key] === $searchValue;
            }
        });
        return $foundMembers;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function insertToDb(): int
    {
        $countUpdated = 0;
        $countNotUpdated = 0;
        $allRoles = $this->fetchRoller();

        foreach ($this->data as $row) {
            //First populate a member object with the row data and save it
            $member = new Medlem($this->conn, $this->app->getLogger());
            $birthdate = DateFormatter::formatDateWithHms($row['Födelsedatum']);
            $member->fodelsedatum = $birthdate ?: "";
            if (!empty($this->fodelsedatum)) {
                print_r($member->fodelsedatum);
                exit;
            }
            $member->fornamn = $row['Förnamn'];
            $member->efternamn = $row['Efternamn'];
            $member->email = isset($row['E-post']) ? $row['E-post'] : "";
            $member->mobil = isset($row['Mobiltelefon']) ? $row['Mobiltelefon'] : "";
            $member->telefon = isset($row['Telefonnr']) ? $row['Telefonnr'] : "";
            $member->adress = isset($row['Adress']) ? $row['Adress'] : "";
            $member->postnummer = isset($row['Postnr']) ? $row['Postnr'] : "";
            $member->postort = isset($row['Ort']) ? $row['Ort'] : "";
            $member->kommentar = isset($row['Kommentar']) ? $row['Kommentar'] : "";
            $member->pref_kommunikation = (isset($row['EjUtskick']) &&  $row['EjUtskick'] === "Nej") ? false : true;
            $member->foretag = (empty($row['Företag']) ? false : true);
            $member->godkant_gdpr = false;
            $member->isAdmin = false;
            $member->skickat_valkomstbrev = false;
            //if B24 = SM then it's a lifetime member who don't need to pay yearly membership if not set betalning
            if ($row['B24'] === "SM") {
                $member->standig_medlem = true;
            } else {
                $member->standig_medlem = false;
            }
            //$member->created_at = $row['created_at'];
            //$member->updated_at = $row['updated_at'];
            try {
                $insertedId = $member->create();
                $member->id = $insertedId;
                $countUpdated++;
                //Add betalningar for member
                $betalningar = array_filter([
                    '2024' => DateFormatter::formatDateWithHms($row['B24']),
                    '2023' => DateFormatter::formatDateWithHms($row['B23']),
                    '2022' => DateFormatter::formatDateWithHms($row['B22'])
                ]);
                if (!empty($betalningar)) {
                    $this->addPaymentsForMember($member->id, $betalningar);
                }
                //Add roller to Medlem
                $this->addRolesForMember($member->id, $row['Besättning'], $row['Underhåll'], $allRoles);
                //Finish off by returning if it went well or not
            } catch (PDOException $e) {
                $countNotUpdated++;
                $this->dbRowsNotCreated[] = implode(",", $row);
            }
        }
        $countTotal = $countUpdated + $countNotUpdated;
        echo "------------LADDAT DATABAS-----------" . PHP_EOL;
        echo "Totalt antal rader: " . $countTotal . PHP_EOL;
        echo "Antal rader sparade: " . $countUpdated . PHP_EOL;
        echo "Antal rader ej sparade: " . $countNotUpdated . PHP_EOL;
        return $countUpdated;
    }

    public function deleteMedlemmar(): void
    {
        $query = 'DELETE FROM Medlem;';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $query = 'DELETE FROM Medlem_Roll;';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }

    private function readCsv(): array
    {
        $data = [];
        $file = fopen($this->csvfile, 'r');
        $validRows = 0;
        $inValidRows = 0;

        if ($file !== false) {
            // Read the header row to get column names
            $headers = fgetcsv($file, 0, ',');
            // Read the rest of the rows
            while (($row = fgetcsv($file, 0, ',')) !== false) {
                // Combine the headers and row data into an associative array
                $combinedRow = array_combine($headers, $row);
                if ($this->isValidCsvRow($combinedRow)) {
                    $data[] = $combinedRow;
                    $validRows++;
                } else {
                    $arrayContents = implode(',', $row);
                    $this->csvRowsNotImported[] = $arrayContents;
                    $inValidRows++;
                }
            }
            fclose($file);
        }
        $totalRows = $validRows + $inValidRows;
        echo "------------LÄST IN CSV-----------" . PHP_EOL;
        echo "Totalt antal rader: " . $totalRows . PHP_EOL;
        echo "Giltiga rader: " . $validRows . PHP_EOL;
        echo "Ogiltiga rader: " . $inValidRows . PHP_EOL;

        //Print $inValidRows to a file
        $errorFile = $this->app->getRootDir() . '/db/csv-data/csvRowsNotImported.csv';
        if (file_exists($errorFile)) {
            unlink($errorFile);
        }
        $file = fopen($errorFile, 'w');
        foreach ($this->csvRowsNotImported as $row) {
            fputcsv($file, [$row]);
        }
        fclose($file);

        return $data;
    }

    public function isValidCsvRow(array $row): bool
    {
        // Define the expected number of columns based on your CSV header
        $expectedColumns = 32;
        // Check if the row has the correct number of elements
        if (count($row) !== $expectedColumns) {
            return false;
        }

        // Efternamn is mandatory in db
        if (empty($row['Efternamn'])) {
            return false;
        }

        // Verify that Födelsedatum is a valid date
        if (!empty($row['Födelsedatum']) && !strtotime($row['Födelsedatum'])) {
            return false;
        }

        // Trim whitespace from all fields
        foreach ($row as $key => $value) {
            $row[$key] = trim($value);
        }

        return true;
    }

    private function fetchRoller(): array
    {
        $query = 'SELECT * FROM Roll;';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if (empty($result)) {
            throw new \Exception('Inga roller hittade i databasen');
        }
        return $result;
    }

    private function addRolesForMember(int $medlemId, string $besattningRoll, string $underhallRoll, array $allRoles)
    {
        $roles = array_merge(
            explode(',', $besattningRoll),
            explode(',', $underhallRoll)
        );

        foreach ($roles as $roleName) {
            $roleName = trim($roleName);
            if (empty($roleName)) {
                continue;
            }
            $roleId = array_search($roleName, array_column($allRoles, 'roll_namn'));
            if ($roleId !== false) {
                $this->insertMedlemRoll($medlemId, $allRoles[$roleId]['id']);
            }
        }
    }

    private function insertMedlemRoll($medlemId, $roleId)
    {
        $query = "INSERT INTO Medlem_Roll (medlem_id, roll_id) VALUES (:medlem_id, :roll_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':medlem_id', $medlemId);
        $stmt->bindParam(':roll_id', $roleId);
        $stmt->execute();
    }

    private function addPaymentsForMember(int $memberId, array $betalningar): void
    {
        foreach ($betalningar as $year => $date) {
            if (!empty($date)) {
                $query = "INSERT INTO Betalning (medlem_id, belopp, datum, avser_ar, kommentar) 
                    VALUES (:medlem_id, :belopp, :datum, :avser_ar, :kommentar)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':medlem_id', $memberId);
                $stmt->bindValue(':belopp', 300);
                $stmt->bindParam(':datum', $date);
                $stmt->bindParam(':avser_ar', $year);
                $stmt->bindValue(':kommentar', "Automatskapad vid import");
                $stmt->execute();
            }
        }
    }

    public function addJohanWithPwdAndAdmin(): void
    {
        $query = "SELECT id FROM Medlem WHERE fornamn = 'Johan' AND efternamn = 'Klinge';";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $query = "UPDATE Medlem SET password = :pwd, isAdmin = :isAdmin WHERE id = :id;";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':pwd', password_hash('Alfa1212', PASSWORD_DEFAULT));
        $stmt->bindValue(':isAdmin', 1);
        $stmt->bindParam(':id', $result[0]['id']);
        $stmt->execute();
        echo "---Johan Klinge har nu admin-rättigheter" . PHP_EOL;
    }


    private function connect()
    {
        if (!is_file($this->dbfile)) {
            throw new InvalidArgumentException("Invalid path to db file");
        }
        try {
            $this->conn = new PDO("sqlite:" . $this->dbfile);
            $this->conn->exec("PRAGMA foreign_keys = ON;");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new PDOException($exception->getMessage(), (int) $exception->getCode());
        }
    }
}
