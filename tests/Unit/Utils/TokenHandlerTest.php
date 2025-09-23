<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utils\TokenHandler;
use App\Utils\TokenType;
use PDO;
use Monolog\Logger;

class TokenHandlerTest extends TestCase
{
    private TokenHandler $tokenHandler;
    private $mockPdo;
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockLogger = $this->createMock(Logger::class);
        $this->tokenHandler = new TokenHandler($this->mockPdo, $this->mockLogger);
    }

    public function testGenerateTokenReturnsNonEmptyString(): void
    {
        $token = $this->tokenHandler->generateToken();
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateTokenReturnsUniqueValues(): void
    {
        $token1 = $this->tokenHandler->generateToken();
        $token2 = $this->tokenHandler->generateToken();
        $this->assertNotEquals($token1, $token2);
    }

    public function testSaveTokenForActivation(): void
    {
        $token = 'test_token';
        $tokenType = TokenType::ACTIVATION;
        $email = 'test@example.com';
        $hashedPassword = 'hashed_password';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->saveToken($token, $tokenType, $email, $hashedPassword);
        $this->assertTrue($result);
    }

    public function testSaveTokenForPasswordReset(): void
    {
        $token = 'test_token';
        $tokenType = TokenType::RESET;
        $email = 'test@example.com';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->saveToken($token, $tokenType, $email);
        $this->assertTrue($result);
    }

    public function testIsValidTokenWithValidToken(): void
    {
        $token = 'valid_token';
        $tokenType = TokenType::ACTIVATION;
        $email = 'test@example.com';
        $expirationTime = time() + 3600; // 1 hour from now
        $expirationTimeString = date('Y-m-d H:i:s', $expirationTime);

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')
            ->willReturn([
                'email' => $email,
                'created_at' => $expirationTimeString,
                'password_hash' => 'hashed_password',
            ]);

        $this->mockPdo->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->isValidToken($token, $tokenType);

        $this->assertIsArray($result);
        $this->assertEquals($email, $result['email']);
    }

    public function testIsValidTokenWithExpiredToken(): void
    {
        $token = 'expired_token';
        $tokenType = TokenType::RESET;
        $expirationTime = time() - 3600; // 1 hour ago
        $expirationTimeString = date('Y-m-d H:i:s', $expirationTime);
        $expectedResult = ['success' => false, 'message' => 'Länkens giltighetstid är 30 min. Den fungerar inte längre. Försök igen'];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')
            ->willReturn([
                'email' => 'test@example.com',
                'created_at' => (string) $expirationTimeString,
            ]);

        $this->mockPdo->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->isValidToken($token, $tokenType);
        $this->assertEquals($expectedResult, $result);
    }

    public function testDeleteToken(): void
    {
        $token = 'token_to_delete';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->deleteToken($token);
        $this->assertTrue($result);
    }

    public function testDeleteExpiredTokens(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmt->method('rowCount')
            ->willReturn(5);

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->deleteExpiredTokens();
        $this->assertEquals(5, $result);
    }

    public function testIsValidTokenWithInvalidToken(): void
    {
        $token = 'invalid_token';
        $tokenType = TokenType::ACTIVATION;
        $expectedReturn = ['success' => false, 'message' => 'Länken är inte giltig'];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')
            ->willReturn(false);

        $this->mockPdo->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->isValidToken($token, $tokenType);
        $this->assertEquals($expectedReturn, $result);
    }

    public function testSaveTokenFailure(): void
    {
        $token = 'test_token';
        $tokenType = TokenType::RESET;
        $email = 'test@example.com';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willThrowException(new \PDOException('Database error'));

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->saveToken($token, $tokenType, $email);
        $this->assertFalse($result);
    }

    public function testDeleteTokenFailure(): void
    {
        $token = 'token_to_delete';

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willThrowException(new \PDOException('Database error'));

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = $this->tokenHandler->deleteToken($token);
        $this->assertFalse($result);
    }
}
