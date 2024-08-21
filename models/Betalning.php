<?php
class Betalning
{

    // database connection and table name
    private $conn;
    private $table_name = "Betalning";

    // object properties
    public int $id;
    public int $medlem_id;
    public float $belopp;
    public string $datum;
    public int $avser_ar;
    public string $kommentar;
    public string $created_at;
    public string $updated_at;

    public function __construct($db, $paymentData = null)
    {
        $this->conn = $db;

        //if created with paymentsData set the properties
        if ($paymentData !== null) {
            $this->id = $paymentData['id'];
            $this->belopp = $paymentData['belopp'];
            $this->medlem_id = $paymentData['medlem_id'];
            $this->datum = $paymentData['datum'];
            $this->avser_ar = $paymentData['avser_ar'];
            $this->kommentar = $paymentData['kommentar'];
            $this->created_at = $paymentData['created_at'];
            $this->updated_at = $paymentData['updated_at'];
        }
    }

    public function get($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? limit 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->id = $id;
        $this->belopp = $row['belopp'];
        $this->medlem_id = $row['medlem_id'];
        $this->datum = isset($row['datum']) ? $row['datum'] : "";
        $this->avser_ar = $row['avser_ar'];
        $this->kommentar = isset($row['kommentar']) ? $row['kommentar'] : "";
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }
    
}