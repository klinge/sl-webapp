<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\Database;
use PDO;
use PDOException;
use Monolog\Logger;
use ReflectionClass;

class DatabaseTest extends TestCase
{
    private $logger;
    private $dbPath;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->dbPath = ':memory:'; // Use SQLite in-memory database for testing

        // Reset singleton instance before each test
        $this->resetSingleton();
    }

    protected function tearDown(): void
    {
        // Reset singleton instance after each test
        $this->resetSingleton();
    }

    private function resetSingleton(): void
    {
        $reflection = new ReflectionClass(Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
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

    public function testDatabaseFileNotFound()
    {
        $nonExistentPath = '/nonexistent/path/database.db';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Could not connect to the database'));

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Database file not found: ' . $nonExistentPath);

        Database::getInstance($nonExistentPath, $this->logger);
    }

    public function testInvalidDatabasePath()
    {
        // Test with a path that would cause PDO to fail
        $invalidPath = '/dev/null/invalid.db';

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Could not connect to the database'));

        $this->expectException(PDOException::class);

        Database::getInstance($invalidPath, $this->logger);
    }

    public function testSingletonBehaviorWithErrors()
    {
        // First call with invalid path should fail
        $invalidPath = '/nonexistent/database.db';

        $this->logger->expects($this->once())
            ->method('error');

        try {
            Database::getInstance($invalidPath, $this->logger);
            $this->fail('Expected PDOException was not thrown');
        } catch (PDOException $e) {
            // Expected exception
        }

        // Reset singleton for second attempt
        $this->resetSingleton();

        // Second call with valid path should succeed
        $validInstance = Database::getInstance($this->dbPath, $this->logger);
        $this->assertInstanceOf(Database::class, $validInstance);
    }

    public function testMemoryDatabaseWorks()
    {
        // Ensure :memory: database works correctly
        $database = Database::getInstance(':memory:', $this->logger);
        $connection = $database->getConnection();

        $this->assertInstanceOf(PDO::class, $connection);

        // Test that we can create and query a table
        $connection->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $connection->exec("INSERT INTO test (name) VALUES ('test')");

        $result = $connection->query('SELECT COUNT(*) FROM test')->fetchColumn();
        $this->assertEquals(1, $result);
    }

    public function testCloneProtection()
    {
        $database = Database::getInstance($this->dbPath, $this->logger);

        // Use reflection to test the __clone method
        $reflection = new ReflectionClass($database);
        $cloneMethod = $reflection->getMethod('__clone');
        $cloneMethod->setAccessible(true);

        // Should not throw an exception, just prevent cloning
        $cloneMethod->invoke($database);
        $this->assertTrue(true); // If we get here, the method executed without error
    }

    public function testWakeupProtection()
    {
        $database = Database::getInstance($this->dbPath, $this->logger);

        // Test the __wakeup method
        $database->__wakeup();
        $this->assertTrue(true); // If we get here, the method executed without error
    }
}
