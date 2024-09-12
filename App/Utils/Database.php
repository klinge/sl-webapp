<?php

namespace App\Utils;

use PDO;
use PDOException;
use App\Application;

class Database
{
    private static $instance = null;
    private $conn;
    private $dbfile;

    private function __construct(Application $app)
    {
        $this->dbfile = $app->getConfig('DB_PATH');
        //$this->dbfile = "slask";
        $this->connect();
    }

    public static function getInstance(Application $app): Database
    {
        if (self::$instance === null) {
            self::$instance = new self($app);
        }
        return self::$instance;
    }

    private function connect()
    {
        try {
            $this->conn = new PDO("sqlite:" . $this->dbfile);
            $this->conn->exec("PRAGMA foreign_keys = ON;");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new PDOException($exception->getMessage(), (int) $exception->getCode());
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    private function __clone()
    {
        // Prevent cloning of the instance
    }

    public function __wakeup()
    {
        // Prevent unserializing of the instance
    }
}
