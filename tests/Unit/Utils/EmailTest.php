<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Application;
use App\Utils\Email;
use App\Utils\EmailType;
use PHPMailer\PHPMailer\Exception;
use Monolog\Logger;
use ReflectionClass;

class EmailTest extends TestCase
{
    private $app;
    private $logger;
    private $email;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->logger = $this->createMock(Logger::class);

        // Mock each config call individually
        $this->app->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(function ($key) {
                $configs = [
                    'SMTP_HOST' => 'smtp.example.com',
                    'SMTP_USERNAME' => 'testuser',
                    'SMTP_PASSWORD' => 'testpass',
                    'SMTP_PORT' => "587",
                    'SMTP_FROM_NAME' => 'Test Sender',
                    'SMTP_FROM_EMAIL' => 'sender@example.com',
                    'SMTP_REPLYTO' => 'reply@example.com'
                ];
                return $configs[$key] ?? null;
            });

        $this->email = new Email($this->app, $this->logger);
    }

    public function testConfiguration()
    {
        // Use reflection to access private mailer property
        $reflection = new ReflectionClass($this->email);
        $mailerProperty = $reflection->getProperty('mailer');
        $mailerProperty->setAccessible(true);
        $mailer = $mailerProperty->getValue($this->email);

        // Assert SMTP settings
        $this->assertEquals('smtp.example.com', $mailer->Host);
        $this->assertTrue($mailer->SMTPAuth, "SMTP authentication should be enabled");
        $this->assertEquals('testuser', $mailer->Username);
        $this->assertEquals('testpass', $mailer->Password);
        $this->assertEquals(587, $mailer->Port);
        $this->assertEquals(20, $mailer->Timeout);

        // Assert content settings
        $this->assertEquals('UTF-8', $mailer->CharSet);
        $this->assertEquals('text/html; charset=UTF-8', $mailer->ContentType);
    }

    public function testTemplateLoadingAndVariebleReplacement()
    {
        // Mock getRootDir to return our test fixtures path
        $this->app->expects($this->once())
            ->method('getRootDir')
            ->willReturn(__DIR__ . '/../../fixtures');

        $loadTemplate = $this->unprotectLoadTemplate();

        // Test template loading with variable replacement
        $result = $loadTemplate->invoke($this->email, EmailType::TEST, [
            'name' => 'John',
            'type' => 'test'
        ]);

        $this->assertEquals('Hello John, this is a test email!', $result);
    }

    public function testTemplateNotFoundThrowsException()
    {
        $this->app->expects($this->once())
            ->method('getRootDir')
            ->willReturn(__DIR__ . '/../../fixtures');

        $loadTemplate = $this->unprotectLoadTemplate();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email template not found: welcome.tpl');

        $loadTemplate->invoke($this->email, EmailType::WELCOME, []);
    }

    protected function unprotectLoadTemplate()
    {
        // Use reflection to access private loadTemplate method
        $reflection = new ReflectionClass($this->email);
        $loadTemplate = $reflection->getMethod('loadTemplate');
        $loadTemplate->setAccessible(true);
        return $loadTemplate;
    }
}
