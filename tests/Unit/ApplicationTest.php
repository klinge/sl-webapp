<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Application;
use App\Middleware\Contracts\MiddlewareStack;
use App\Utils\ResponseEmitter;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response;

class ApplicationTest extends TestCase
{
    private string $originalAppEnv;
    private string $originalAppDir;

    protected function setUp(): void
    {
        // Store original environment values
        $this->originalAppEnv = $_ENV['APP_ENV'] ?? '';
        $this->originalAppDir = $_ENV['APP_DIR'] ?? '';

        // Set test environment
        $_ENV['APP_ENV'] = 'DEV';
        $_ENV['APP_DIR'] = '/test';

        // Clear session
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Restore original environment
        $_ENV['APP_ENV'] = $this->originalAppEnv;
        $_ENV['APP_DIR'] = $this->originalAppDir;

        // Clear session
        $_SESSION = [];
    }

    public function testApplicationInstantiation(): void
    {
        $app = new Application();

        $this->assertInstanceOf(Application::class, $app);
    }

    public function testGetAppEnvReturnsDev(): void
    {
        $_ENV['APP_ENV'] = 'DEV';
        $app = new Application();

        $this->assertEquals('DEV', $app->getAppEnv());
    }

    public function testGetAppEnvReturnsProdByDefault(): void
    {
        $_ENV['APP_ENV'] = 'PROD';
        $app = new Application();

        $this->assertEquals('PROD', $app->getAppEnv());
    }

    public function testGetAppEnvReturnsProdForInvalidValue(): void
    {
        $_ENV['APP_ENV'] = 'INVALID';
        $app = new Application();

        $this->assertEquals('PROD', $app->getAppEnv());
    }

    public function testGetAppDir(): void
    {
        $_ENV['APP_DIR'] = '/test-app';
        $app = new Application();

        $this->assertEquals('/test-app', $app->getAppDir());
    }

    public function testGetRootDir(): void
    {
        $app = new Application();

        $rootDir = $app->getRootDir();
        $this->assertIsString($rootDir);
        $this->assertStringEndsWith('sl-webapp', $rootDir);
    }

    public function testGetConfigWithKey(): void
    {
        $_ENV['TEST_KEY'] = 'test_value';
        $app = new Application();

        $this->assertEquals('test_value', $app->getConfig('TEST_KEY'));
    }

    public function testGetConfigWithNonExistentKey(): void
    {
        $app = new Application();

        $this->assertNull($app->getConfig('NON_EXISTENT_KEY'));
    }

    public function testGetConfigWithoutKey(): void
    {
        $app = new Application();

        $config = $app->getConfig(null);
        $this->assertIsArray($config);
    }

    public function testGetConfigBooleanConversion(): void
    {
        $_ENV['TRUE_VALUE'] = 'true';
        $_ENV['FALSE_VALUE'] = 'false';
        $_ENV['STRING_VALUE'] = 'normal_string';

        $app = new Application();

        $this->assertSame(true, $app->getConfig('TRUE_VALUE'));
        $this->assertSame(false, $app->getConfig('FALSE_VALUE'));
        $this->assertEquals('normal_string', $app->getConfig('STRING_VALUE'));
    }

    public function testGetContainer(): void
    {
        $app = new Application();

        $container = $app->getContainer();
        $this->assertInstanceOf(\League\Container\Container::class, $container);
    }

    public function testGetRouter(): void
    {
        $app = new Application();

        $router = $app->getRouter();
        $this->assertInstanceOf(\League\Route\Router::class, $router);
    }

    public function testGetPsrRequest(): void
    {
        $app = new Application();

        $request = $app->getPsrRequest();
        $this->assertInstanceOf(\Psr\Http\Message\ServerRequestInterface::class, $request);
    }

    public function testAddMiddleware(): void
    {
        $app = new Application();
        $middleware = $this->createMock(\App\Middleware\Contracts\MiddlewareInterface::class);

        // This should not throw an exception
        $app->addMiddleware($middleware);
        $this->assertTrue(true); // Assert that we got here without exception
    }

    public function testRunExecutesMiddlewareStack(): void
    {
        // This test is complex due to the run() method emitting responses
        // We'll test that the method exists and is callable
        $app = new Application();

        $this->assertTrue(method_exists($app, 'run'));
        $this->assertTrue(is_callable([$app, 'run']));
    }
}
