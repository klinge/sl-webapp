<?php

declare(strict_types=1);

namespace App\Utils;

use PDO;
use PDOException;
use App\Application;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Utils\DateFormatter;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * @deprecated This class will is just for initial database import and will be deprecated in upcoming releases.
 */
class CsvImporter
{
    /** @var array<int, array<string, string>> */
    public array $data = [];
    private PDO $conn;
    private string $dbfile;
    private string $csvfile;
    /** @var array<int, string> */
    public array $csvRowsNotImported = [];
    /** @var array<int, string> */
    public array $dbRowsNotCreated = [];
    public Application $app;
    public LoggerInterface $logger;
    private MedlemRepository $medlemRepo;

    public function __construct(string $dbFilename)
    {
        $this->app = new Application();
        $this->logger = $this->app->getContainer()->get(LoggerInterface::class);
        $this->medlemRepo = $this->app->getContainer()->get(MedlemRepository::class);
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

    /**
     * @return array<int, array<string, string>>
     */
    public function findMembersInCsv(string $key, string $searchValue): array
    {
        // Print some things to see if import went well
        $foundMembers = array_filter($this->data, function (array $member) use ($key, $searchValue): bool {
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
            $member = new Medlem();
            $birthdate = DateFormatter::formatDateWithHms($row['Födelsedatum']);
            $member->fodelsedatum = $birthdate ?: "";
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
            try {
                $success = $this->medlemRepo->save($member);
                if ($success) {
                    $countUpdated++;
                    //Add betalningar for member
                    /** @var array<string, string> */
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
                } else {
                    $countNotUpdated++;
                    $this->dbRowsNotCreated[] = implode(",", $row);
                }
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
        $query = 'DELETE FROM Betalning;';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(): array
    {
        /** @var array<int, array<string, string>> */
        $data = [];
        $file = fopen($this->csvfile, 'r');
        $validRows = 0;
        $inValidRows = 0;

        if ($file !== false) {
            // Read the header row to get column names
            $headers = fgetcsv($file, 0, ',');
            if ($headers === false) {
                fclose($file);
                return $data;
            }
            // Read the rest of the rows
            while (($row = fgetcsv($file, 0, ',')) !== false) {
                // Combine the headers and row data into an associative array
                // array_combine can return false if the number of elements do not match or if either array is empty
                $combinedRow = array_combine($headers, $row);
                // verify that $combinedRow is not false and that the row is valid
                // @phpstan-ignore-next-line
                if ($combinedRow !== false && $this->isValidCsvRow($combinedRow)) {
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
        if ($file !== false) {
            foreach ($this->csvRowsNotImported as $row) {
                fputcsv($file, [$row]);
            }
            fclose($file);
        }

        return $data;
    }

    /**
     * @param array<string, string> $row
     */
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

    /**
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @param array<int, array<string, mixed>> $allRoles
     */
    private function addRolesForMember(int $medlemId, string $besattningRoll, string $underhallRoll, array $allRoles): void
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

    private function connect(): void
    {
        if (!file_exists($this->dbfile)) {
            throw new InvalidArgumentException("Database file does not exist: " . $this->dbfile);
        }
        $this->conn = new PDO('sqlite:' . $this->dbfile);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function insertMedlemRoll(int $medlemId, int $roleId): void
    {
        $query = 'INSERT INTO Medlem_Roll (medlem_id, roll_id) VALUES (?, ?)';
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$medlemId, $roleId]);
    }

    /**
     * @param array<string, string> $betalningar
     */
    private function addPaymentsForMember(int $medlemId, array $betalningar): void
    {
        foreach ($betalningar as $year => $date) {
            if (!empty($date)) {
                $query = 'INSERT INTO Betalning (medlem_id, belopp, datum, avser_ar) VALUES (?, ?, ?, ?)';
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$medlemId, 500, $date, (int) $year]); // Default amount 500
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
}
