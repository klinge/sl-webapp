<?php 

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

class BaseController{  

    protected $conn;
    protected $request;
    protected $router;

    public function __construct($request, $router) 
    {
        $this->request = $request;
        $this->conn = $this->getDatabaseConn();
        $this->router = $router;
    }

    private function getDatabaseConn() {
        // get database connection
        $database = new Database();
        return $database->getConnection();
    }

    protected function sanitizeInput($data) {
        $data = trim($data); // Remove leading and trailing whitespace
        $data = stripslashes($data); // Remove backslashes
      
        // Sanitize based on data type
        if (is_numeric($data)) {
          $data = intval($data); // Convert to integer (removes non-numeric characters)
        } else if (is_string($data)) {
          $data = htmlspecialchars($data, ENT_QUOTES); // Escape special characters for HTML output
        } else {
          // TODO Handle other data types or throw an exception for unexpected types
        }
      
        return $data;
      }

}