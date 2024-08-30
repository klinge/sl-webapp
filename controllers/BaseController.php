<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Session.php';

class BaseController
{

  protected $conn;
  protected $request;
  protected $router;
  protected $sessionData;

  public function __construct($request, $router)
  {
    Session::start();
    $this->initializeSessionData();
    $this->request = $request;
    $this->conn = $this->getDatabaseConn();
    $this->router = $router;
  }

  protected function initializeSessionData()
  {
    $this->sessionData = [
      'isLoggedIn' => Session::isLoggedIn(),
      'userId' => Session::get('user_id'),
      'fornamn' => Session::get('fornamn'),
      'isAdmin' => Session::isAdmin()
    ];
  }

  protected function render(string $viewUrl, array $data = []) {
    // Merge the session data with the view-specific data
    $viewData = array_merge($this->sessionData, $data);
    require __DIR__ . $viewUrl;
}

  private function getDatabaseConn()
  {
    // get database connection
    $database = new Database();
    return $database->getConnection();
  }

  protected function jsonResponse(array $data)
  {
    // Set the content type to JSON
    header('Content-Type: application/json');

    // Encode the data as JSON
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);

    // Check for encoding errors
    if (json_last_error() !== JSON_ERROR_NONE) {
      // Handle encoding errors gracefully
      $jsonData = json_encode(['success' => false, 'message' => 'Error encoding data']);
    }

    // Send the JSON response
    echo $jsonData;
    exit;
  }


  protected function sanitizeInput($data)
  {
    if (!empty($data)) {
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
    } else {
      return $data;
    }
  }

  protected function validateDate($date)
  {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date ? $date : false;
  }

  protected function requireAuth()
  {
    Session::start();
    if (!Session::isLoggedIn()) {
      echo "Requires login!";
      //header('Location: /login');
      exit;
    }
  }

  protected function requireAdmin()
  {
    Session::start();
    if (!Session::isAdmin()) {
      echo "Requires admin!";
      //header('Location: /login');
      exit;
    }
  }
}
