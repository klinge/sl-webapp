<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Application;
use App\Services\Auth\UserAuthenticationService;
use App\Utils\Email;
use Monolog\Logger;
use AltoRouter;
use PDO;

class UserAuthenticationServiceTest extends TestCase
{
    private $app;
    private $conn;
    private $authService;
    private $logger;
    private $router;
    private $mailer;
    private $memberData;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->conn = $this->createMock(PDO::class);
        $this->logger = $this->createMock(Logger::class);
        $this->router = $this->createMock(AltoRouter::class);
        $this->mailer = $this->createMock(Email::class);

        $this->app->method('getLogger')->willReturn($this->logger);
        $this->app->method('getRouter')->willReturn($this->router);
        $this->app->method('getConfig')->willReturn('http://test.com');

        $this->authService = new UserAuthenticationService($this->conn, $this->app, $this->mailer);

        $this->memberData = [
            'id' => 1,
            'fornamn' => 'John',
            'efternamn' => 'Doe',
            'email' => 'test@example.com',
            'password' => null,
            'godkant_gdpr' => 1,
            'pref_kommunikation' => 1,
            'foretag' => 0,
            'standig_medlem' => 1,
            'skickat_valkomstbrev' => 0,
            'isAdmin' => 0,
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-01 00:00:00'
        ];
    }

    public function testRegisterUserSuccess(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'password' => 'ValidPass123',
            'verifyPassword' => 'ValidPass123'
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $this->conn->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($this->memberData);

        $result = $this->authService->registerUser($formData);
        $this->assertTrue($result['success']);
    }

    public function testRegisterUserFailEmailNotFound(): void
    {
        $formData = [
            'email' => 'nonexistent@example.com',
            'password' => 'ValidPass123',
            'verifyPassword' => 'ValidPass123'
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $this->conn->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(false);

        $result = $this->authService->registerUser($formData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('finns ingen medlem', $result['message']);
    }

    public function testActivateAccountSuccess(): void
    {
        $token = 'valid_token';

        $tokenStmt = $this->createMock(\PDOStatement::class);
        $tokenStmt->method('fetch')->willReturn([
            'token' => 'valid_token',
            'email' => 'test@example.com',
            'password_hash' => 'hashed_password_value',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $memberStmt = $this->createMock(\PDOStatement::class);
        $memberStmt->method('fetch')->willReturn($this->memberData);

        // Map different SQL queries to different statement mocks
        $this->conn->method('prepare')
            ->willReturnCallback(function ($sql) use ($tokenStmt, $memberStmt) {
                if (strpos($sql, 'token') !== false) {
                    return $tokenStmt;
                }
                return $memberStmt;
            });

        $result = $this->authService->activateAccount($token);
        $this->assertTrue($result['success']);
    }

    public function testActivateAccountFailTokenError(): void
    {
        $token = 'invalid_token';

        $stmt = $this->createMock(\PDOStatement::class);
        $this->conn->method('prepare')->willReturn($stmt);
        $stmt->method('fetch')->willReturn([
            'token' => 'valid_token',
            'email' => 'test@example.com',
            'password_hash' => 'hashed_password_value',
            'created_at' => '2023-01-01 00:00:00', //time has expired
        ]);

        $result = $this->authService->activateAccount($token);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Länkens giltighetstid', $result['message']);
    }

    public function testSendPasswordResetSuccess(): void
    {
        $email = 'test@example.com';

        $stmt = $this->createMock(\PDOStatement::class);
        $this->conn->method('prepare')->willReturn($stmt);
        $stmt->method('fetch')->willReturn($this->memberData);

        $result = $this->authService->requestPasswordReset($email);
        $this->assertTrue($result['success']);
    }

    public function testSendPasswordResetFailEmailNotFound(): void
    {
        $email = 'nonexistent@example.com';
        $stmt = $this->createMock(\PDOStatement::class);
        $this->conn->method('prepare')->willReturn($stmt);
        $stmt->method('fetch')->willReturn('');

        $result = $this->authService->requestPasswordReset($email);
        $this->assertFalse($result['success']);
    }

    public function testResetPasswordSuccess(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'token' => 'valid_token',
            'password' => 'NewPass123',
            'password2' => 'NewPass123'
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $this->conn->method('prepare')->willReturn($stmt);
        $stmt->method('fetch')->willReturn($this->memberData);

        $result = $this->authService->resetPassword($formData);
        $this->assertTrue($result['success']);
    }

    public function testResetPasswordFailPasswordsDontMatch(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'token' => 'valid_token',
            'password' => 'NewPass123',
            'password2' => 'DifferentPass123'
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $this->conn->method('prepare')->willReturn($stmt);
        $stmt->method('fetch')->willReturn($this->memberData);

        $result = $this->authService->resetPassword($formData);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('stämmer inte överens', $result['message']);
    }
}
