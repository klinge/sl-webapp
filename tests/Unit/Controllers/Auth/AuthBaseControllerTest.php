<?php

namespace Tests\Unit\Controllers\Auth;

use PHPUnit\Framework\TestCase;
use App\Application;
use Psr\Http\Message\ServerRequestInterface;
use andkab\Turnstile\Turnstile;
use andkab\Turnstile\Response;
use Monolog\Logger;

class AuthBaseControllerTest extends TestCase
{
    private $app;
    private $request;
    private $controller;
    private $conn;
    private $logger;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->conn = $this->createMock(\PDO::class);
        $this->logger = $this->createMock(Logger::class);

        // Mock Database singleton
        $database = $this->createMock(\App\Utils\Database::class);
        $database->method('getConnection')
            ->willReturn($this->conn);

        // Set up Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, $database);

        // Mock the config
        $this->app->method('getConfig')
            ->willReturnMap([
                ['TURNSTILE_SECRET_KEY', 'test-secret-key']
            ]);

        // Mock server params
        $this->request->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $this->controller = new FakeBaseAuthController($this->app, $this->request, $this->logger);
    }

    protected function tearDown(): void
    {
        // Reset Database singleton
        $reflection = new \ReflectionClass(\App\Utils\Database::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testValidateRecaptchaSuccess(): void
    {
        // Mock Turnstile response
        $turnstileResponse = $this->createMock(Response::class);
        $turnstileResponse->method('isSuccess')
            ->willReturn(true);

        // Mock Turnstile
        $turnstile = $this->createMock(Turnstile::class);
        $turnstile->method('verify')
            ->willReturn($turnstileResponse);

        // Set mocked Turnstile in controller
        $this->setPrivateProperty($this->controller, 'turnstile', $turnstile);

        //Mock request body
        $this->request->method('getParsedBody')
            ->willReturn(['cf-turnstile-response' => 'valid-token']);

        $result = $this->invokeMethod($this->controller, 'validateRecaptcha');

        $this->assertTrue($result, 'Result should be true, was: ' . $result);
    }

    public function testValidateRecaptchaFailure(): void
    {
        // Mock Turnstile response
        $turnstileResponse = $this->createMock(Response::class);
        $turnstileResponse->method('isSuccess')
            ->willReturn(false);

        // Mock Turnstile
        $turnstile = $this->createMock(Turnstile::class);
        $turnstile->method('verify')
            ->willReturn($turnstileResponse);

        // Set mocked Turnstile in controller
        $this->setPrivateProperty($this->controller, 'turnstile', $turnstile);

        $this->request->method('getParsedBody')
            ->willReturn(['cf-turnstile-response' => 'invalid-token']);

        $result = $this->invokeMethod($this->controller, 'validateRecaptcha');
        $this->assertFalse($result);
    }

    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    private function setPrivateProperty($object, $property, $value): void
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
