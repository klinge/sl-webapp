<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Database;
use App\Application;
use PDO;
use PDOException;

class DatabaseTest extends TestCase
{
    private $mockApp;

    protected function setUp(): void
    {
        $this->mockApp = $this->createMock(Application::class);
    }

    public function testGetInstance()
    {
        $this->mockApp->method('getConfig')->willReturn(':memory:');

        $instance1 = Database::getInstance($this->mockApp);
        $instance2 = Database::getInstance($this->mockApp);

        $this->assertInstanceOf(Database::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    public function testGetConnection()
    {
        $this->mockApp->method('getConfig')->willReturn(':memory:');

        $database = Database::getInstance($this->mockApp);
        $connection = $database->getConnection();

        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testForeignKeysEnabled()
    {
        $this->mockApp->method('getConfig')->willReturn(':memory:');

        $database = Database::getInstance($this->mockApp);
        $connection = $database->getConnection();

        $result = $connection->query("PRAGMA foreign_keys")->fetchColumn();
        $this->assertEquals(1, $result);
    }

    public function testErrorMode()
    {
        $this->mockApp->method('getConfig')->willReturn(':memory:');

        $database = Database::getInstance($this->mockApp);
        $connection = $database->getConnection();

        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $connection->getAttribute(PDO::ATTR_ERRMODE));
    }

    public function testDefaultFetchMode()
    {
        $this->mockApp->method('getConfig')->willReturn(':memory:');

        $database = Database::getInstance($this->mockApp);
        $connection = $database->getConnection();

        $this->assertEquals(PDO::FETCH_ASSOC, $connection->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
    }
}
