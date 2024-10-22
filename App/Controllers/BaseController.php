<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Application;
use App\Traits\JsonResponder;
use App\Utils\Session;
use App\Utils\Database;
use PDO;
use PDOException;
use Psr\Http\Message\ServerRequestInterface;

class BaseController
{
    //Add the JsonResponder trait
    use JsonResponder;

    protected PDO $conn;
    protected ServerRequestInterface $request;
    protected array $sessionData;
    protected Application $app;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        $this->app = $app;
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

    private function getDatabaseConn(): PDO|false
    {
        try {
            return Database::getInstance($this->app)->getConnection();
        } catch (PDOException $e) {
            $this->app->getLogger()->error('Failed to connect to database: ' . $e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
            Session::setFlashMessage('error', 'Tekniskt fel. Kunde inte öppna databas. Fel: ' . $e->getMessage());
            header("Location: " . $this->createUrl('home'));
            return false;
        }
    }

    protected function setCsrfToken(): void
    {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
    }

    protected function validateCsrfToken(string $token): bool
    {
        return Session::get('csrf_token') && hash_equals(Session::get('csrf_token'), $token);
    }

    protected function createUrl(string $routeName, array $params = []): string
    {
        return $this->app->getRouter()->generate($routeName, $params);
    }
}
