<?php

namespace App\Controllers;

use Datetime;
use App\Application;
use App\Utils\Session;
use App\Utils\Database;
use PDO;
use PDOException;

class BaseController
{
    protected $conn;
    protected $request;
    protected $router;
    protected $sessionData;
    protected $app;

    public function __construct(Application $app, $request, $router)
    {
        Session::start();
        $this->app = $app;
        $this->router = $router;
        $this->request = $request;
        $this->initializeSessionData();
        $this->conn = $this->getDatabaseConn();
    }

    protected function initializeSessionData(): void
    {
        $this->sessionData = [
            'isLoggedIn' => Session::isLoggedIn(),
            'userId' => Session::get('user_id'),
            'fornamn' => Session::get('fornamn'),
            'isAdmin' => Session::isAdmin()
        ];
    }

    protected function render(string $viewName, array $data = []): void
    {
        // Merge the session data with the view-specific data
        $viewData = array_merge($this->sessionData, $data);
        $viewData['APP_DIR'] = $this->app->getAppDir();
        $viewData['BASE_URL'] = $this->app->getBaseUrl();
        require $_SERVER['DOCUMENT_ROOT'] . "/sl-webapp/views/" . $viewName . ".php";
    }

    private function getDatabaseConn(): PDO|false
    {
        try {
            return Database::getInstance($this->app)->getConnection();
        } catch (PDOException $e) {
            Session::setFlashMessage('error', 'Tekniskt fel. Kunde inte öppna databas. Fel: ' . $e->getMessage());
            header("Location: " . $this->createUrl('home'));
            return false;
        }
    }

    protected function jsonResponse(array $data): string|false
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

    protected function createUrl(string $routeName, array $params = []): string
    {
        return $this->router->generate($routeName, $params);
    }


    protected function sanitizeInput($data)
    {
        if (!empty($data)) {
            $data = trim($data); // Remove leading and trailing whitespace
            $data = stripslashes($data); // Remove backslashes

            // Sanitize based on data type
            if (is_numeric($data)) {
                $data = intval($data); // Convert to integer (removes non-numeric characters)
            } elseif (filter_var($data, FILTER_VALIDATE_EMAIL)) {
                $data = filter_var($data, FILTER_SANITIZE_EMAIL);
            } elseif (is_string($data)) {
                $data = htmlspecialchars($data, ENT_QUOTES); // Escape special characters for HTML output
            } else {
                // TODO Handle other data types or throw an exception for unexpected types
            }

            return $data;
        } else {
            return $data;
        }
    }

    protected function validateDate($date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date ? $date : false;
    }

    protected function requireLogin(): bool
    {
        if (Session::isLoggedIn()) {
            return true;
        } else {
            Session::setFlashMessage('info', 'Du måste vara inloggad för att kunna visa denna sida.');
            //Save current url in session to redirect to after login
            $currentUrl = $this->request['REQUEST_URI'];
            Session::set('redirect_url', $currentUrl);
            $this->render('login/viewLogin');
            exit;
        }
    }

    protected function requireAdmin(): bool
    {
        if (!Session::isAdmin()) {
            return false;
            //TODO show error message to user
        }
        return true;
    }
}
