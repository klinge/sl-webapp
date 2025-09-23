<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Database;
use PDO;
use Monolog\Logger;

class DatabaseTest extends TestCase
{
    private $logger;
    private $dbPath;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->dbPath = ':memory:'; // Use SQLite in-memory database for testing
    }

    public function testGetInstance()
    {
        $instance1 = Database::getInstance($this->dbPath, $this->logger);
        $instance2 = Database::getInstance($this->dbPath, $this->logger);

        $this->assertInstanceOf(Database::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function testGetConnection()
    {
        $database = Database::getInstance($this->dbPath, $this->logger);
        $connection = $database->getConnection();

        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testForeignKeysEnabled()
    {
        $database = Database::getInstance($this->dbPath, $this->logger);
        $connection = $database->getConnection();

        $result = $connection->query("PRAGMA foreign_keys")->fetchColumn();
        $this->assertEquals(1, $result);
    }

    public function testErrorMode()
    {
        $database = Database::getInstance($this->dbPath, $this->logger);
        $connection = $database->getConnection();

        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $connection->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testDefaultFetchMode()
    {
        $database = Database::getInstance($this->dbPath, $this->logger);
        $connection = $database->getConnection();

        $this->assertEquals(PDO::FETCH_ASSOC, $connection->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }
}
