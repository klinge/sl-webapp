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

    public function create()
    {
        try {
            $query = "INSERT INTO " . $this->table_name . " (medlem_id, belopp, datum, avser_ar, kommentar) VALUES (?, ?, ?, ?, ?)";
    
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->medlem_id);
            $stmt->bindParam(2, $this->belopp);
            $stmt->bindParam(3, $this->datum);
            $stmt->bindParam(4, $this->avser_ar);
            $stmt->bindValue(5, $this->kommentar, PDO::PARAM_STR);
    
            if ($stmt->execute()) {
                $newBetalningId = $this->conn->lastInsertId();
                return ['success' => true, 'message' => 'Betalning created successfully', 'id' => $newBetalningId];
            } else {
                $error = $stmt->errorInfo();
                return ['success' => false, 'message' => 'Error creating Betalning: ' . $error[2]];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Unexpected error: ' . $e->getMessage()];
        }
    }

    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Betalning deleted successfully'];
        } else {
            $error = $stmt->errorInfo();
            return ['success' => false, 'message' => 'Error deleting Betalning: ' . $error[2]];
        }
    }
    
}