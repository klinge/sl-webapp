<?php

declare(strict_types=1);

namespace App\Utils;

use PDO;
use PDOException;
use App\Application;

class Database
{
    private static $instance = null;
    private $conn;
    private $dbfile;
    private $app;

    private function __construct(Application $app)
    {
        $this->app = $app;
        $this->dbfile = $app->getConfig('DB_PATH');
        if ($this->dbfile !== ':memory:' && !file_exists($this->dbfile)) {
            throw new PDOException("Database file not found: {$this->dbfile}");
        }
        //$this->dbfile = "slask";
        $this->connect();
    }

    public static function getInstance(Application $app): Database
    {
        if (self::$instance === null) {
            try {
                self::$instance = new self($app);
            } catch (PDOException $e) {
                $app->getLogger()->error("Could not connect to the database. Error: {$e}");
                throw new PDOException($e->getMessage(), (int) $e->getCode());
            }
        }
        return self::$instance;
    }

    private function connect(): void
    {
        try {
            $this->conn = new PDO("sqlite:" . $this->dbfile);
            $this->conn->exec("PRAGMA foreign_keys = ON;");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            $this->app->getLogger()->error("Could not connect to the database. Error: {$exception}");
            throw new PDOException($exception->getMessage(), (int) $exception->getCode());
        }
    }

    public function getConnection(): ?PDO
    {
        return $this->conn;
    }

    private function __clone(): void
    {
        // Prevent cloning of the instance
    }

    public function __wakeup(): void
    {
        // Prevent unserializing of the instance
    }
}
