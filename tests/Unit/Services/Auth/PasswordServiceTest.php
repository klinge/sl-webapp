<?php

namespace Tests\Unit\Services\Auth;

use PHPUnit\Framework\TestCase;
use App\Services\Auth\PasswordService;
use App\Application;

class PasswordServiceTest extends TestCase
{
    private $app;
    private $passwordService;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->passwordService = new PasswordService();
    }

    public function testHashPassword(): void
    {
        $password = "TestPass123";
        $hash = $this->passwordService->hashPassword($password);

        $this->assertIsString($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function testVerifyPasswordSuccess(): void
    {
        $password = "TestPass123";
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertTrue($this->passwordService->verifyPassword($password, $hash));
    }
    public function testVerifyPasswordFalse(): void
    {
        $password = "TestPass123";
        $wrongPassword = "WrongPass123";
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertFalse($this->passwordService->verifyPassword($wrongPassword, $hash));
    }
    public function testPasswordsMatchTrue(): void
    {
        $password = "TestPass123";
        $confirmPassword = "TestPass123";

        $this->assertTrue($this->passwordService->passwordsMatch($password, $confirmPassword));
    }
    public function testPasswordsMatchFalse(): void
    {
        $password = "TestPass123";
        $confirmPassword = "TestPass1234";

        $this->assertFalse($this->passwordService->passwordsMatch($password, $confirmPassword));
    }
    public function testFormatPasswordErrors(): void
    {
        $errors = ["Error 1", "Error 2"];
        $expected = "<ul><li>Error 1</li><li>Error 2</li></ul>";

        $this->assertEquals($expected, $this->passwordService->formatPasswordErrors($errors));
    }
    public function testValidatePasswordNoError(): void
    {
        $password = "TestPass123";
        $email = "john.doe@example.com";

        $errors = $this->passwordService->validatePassword($password, $email);
        $this->assertEmpty($errors);
    }
    public function testValidatePasswordWithError(): void
    {
        $testCases = [
            ["password" => "short", "email" => "test@example.com", "expectedErrors" => 3],
            ["password" => "nouppercase123", "email" => "test@example.com", "expectedErrors" => 1],
            ["password" => "NOLOWERCASE123", "email" => "test@example.com", "expectedErrors" => 1],
            ["password" => "NoNumbers", "email" => "test@example.com", "expectedErrors" => 1],
            ["password" => "john.doePass123", "email" => "john.doe@example.com", "expectedErrors" => 3]
        ];

        foreach ($testCases as $test) {
            $errors = $this->passwordService->validatePassword($test["password"], $test["email"]);
            $this->assertCount($test["expectedErrors"], $errors, "Test case failed for password: " . $test["password"]);
        }
    }
}
