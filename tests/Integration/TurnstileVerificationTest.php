<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Controllers\Auth\PasswordController;
use App\Application;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class TurnstileVerificationTest extends TestCase
{
    private Application $app;
    private PasswordController $controller;

    protected function setUp(): void
    {
        $this->app = new Application();
        $container = $this->app->getContainer();
        $this->controller = $container->get(PasswordController::class);
    }

    public function testTurnstileSecretKeyIsConfigured(): void
    {
        $secretKey = $this->app->getConfig('TURNSTILE_SECRET_KEY');

        $this->assertNotNull($secretKey, 'TURNSTILE_SECRET_KEY should be configured');
        $this->assertNotEmpty($secretKey, 'TURNSTILE_SECRET_KEY should not be empty');
        $this->assertNotEquals('config.TURNSTILE_SECRET_KEY', $secretKey, 'Secret key should be resolved, not literal string');
    }

    public function testTurnstileSecretKeyIsInjectedCorrectly(): void
    {
        // Use reflection to check if the secret key was properly injected
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('turnstileSecret');
        $property->setAccessible(true);
        $injectedSecret = $property->getValue($this->controller);

        $expectedSecret = $this->app->getConfig('TURNSTILE_SECRET_KEY');

        $this->assertEquals($expectedSecret, $injectedSecret, 'Injected secret should match config value');
        $this->assertNotEquals('config.TURNSTILE_SECRET_KEY', $injectedSecret, 'Should not be literal config string');
    }

    public function testValidateRecaptchaHandlesMissingToken(): void
    {
        // Create request without Turnstile token
        $request = ServerRequestFactory::fromGlobals()
            ->withParsedBody([]);

        // Create controller with this request
        $container = $this->app->getContainer();
        $controller = new PasswordController(
            $container->get(\App\Services\UrlGeneratorService::class),
            $request,
            $container->get(\Monolog\Logger::class),
            $container,
            $this->app->getConfig('TURNSTILE_SECRET_KEY'),
            $container->get(\App\Services\Auth\UserAuthenticationService::class),
            $container->get(\App\Utils\View::class)
        );

        // Use reflection to call protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateRecaptcha');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertFalse($result, 'Should return false when token is missing');
    }
}
