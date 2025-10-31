<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BetalningService;
use App\Services\BetalningServiceResult;
use App\Models\BetalningRepository;
use App\Models\MedlemRepository;
use App\Models\Betalning;
use App\Models\Medlem;
use App\Utils\Email;
use App\Utils\EmailType;
use App\Application;
use App\Utils\Session;
use Monolog\Logger;
use Exception;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class BetalningServiceTest extends TestCase
{
    private BetalningService $service;
    private $betalningRepo;
    private $medlemRepo;
    private $email;
    private $app;
    private $logger;

    protected function setUp(): void
    {
        $this->betalningRepo = $this->createMock(BetalningRepository::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);
        $this->email = $this->createMock(Email::class);
        $this->app = $this->createMock(Application::class);
        $this->logger = $this->createMock(Logger::class);

        $this->service = new BetalningService(
            $this->betalningRepo,
            $this->medlemRepo,
            $this->email,
            $this->app,
            $this->logger
        );

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testGetAllPayments(): void
    {
        $expectedPayments = [
            ['id' => 1, 'belopp' => 100],
            ['id' => 2, 'belopp' => 200]
        ];

        $this->betalningRepo->expects($this->once())
            ->method('getAllWithName')
            ->willReturn($expectedPayments);

        $result = $this->service->getAllPayments();

        $this->assertEquals($expectedPayments, $result);
    }

    public function testGetPaymentsForMemberSuccess(): void
    {
        $memberId = 1;
        $mockMedlem = $this->createMockMedlem(1, 'John', 'Doe');
        $expectedPayments = [['id' => 1, 'belopp' => 100]];

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn($mockMedlem);

        $this->betalningRepo->expects($this->once())
            ->method('getBetalningForMedlem')
            ->with($memberId)
            ->willReturn($expectedPayments);

        $result = $this->service->getPaymentsForMember($memberId);

        $this->assertArrayHasKey('medlem', $result);
        $this->assertArrayHasKey('payments', $result);
        $this->assertEquals($mockMedlem, $result['medlem']);
        $this->assertEquals($expectedPayments, $result['payments']);
    }

    public function testGetPaymentsForMemberNotFound(): void
    {
        $memberId = 999;

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Medlem not found');

        $this->service->getPaymentsForMember($memberId);
    }

    public function testCreatePaymentSuccess(): void
    {
        $postData = [
            'medlem_id' => '1',
            'belopp' => '100.50',
            'datum' => '2024-01-01',
            'avser_ar' => '2024',
            'kommentar' => 'Test payment'
        ];

        Session::set('user_id', 123);

        $this->betalningRepo->expects($this->once())
            ->method('create')
            ->willReturn(456);

        $this->app->expects($this->once())
            ->method('getConfig')
            ->with('WELCOME_MAIL_ENABLED')
            ->willReturn('0');

        $result = $this->service->createPayment($postData);

        $this->assertInstanceOf(BetalningServiceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals(456, $result->paymentId);
        $this->assertStringContainsString('456', $result->message);
    }

    public function testCreatePaymentMissingRequiredFields(): void
    {
        $postData = ['belopp' => '100']; // Missing datum and avser_ar

        $result = $this->service->createPayment($postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Belopp, datum, and avser_ar are required fields.', $result->message);
    }

    public function testCreatePaymentInvalidInput(): void
    {
        $postData = [
            'medlem_id' => '0', // Invalid
            'belopp' => '100',
            'datum' => '2024-01-01',
            'avser_ar' => '2024'
        ];

        $result = $this->service->createPayment($postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Invalid input', $result->message);
    }

    public function testCreatePaymentWithWelcomeEmail(): void
    {
        $postData = [
            'medlem_id' => '1',
            'belopp' => '100',
            'datum' => '2024-01-01',
            'avser_ar' => '2024'
        ];

        $mockMedlem = $this->createMockMedlem(1, 'John', 'Doe');
        $mockMedlem->email = 'john@example.com';
        $mockMedlem->skickat_valkomstbrev = false;

        Session::set('user_id', 123);

        $this->betalningRepo->expects($this->once())
            ->method('create')
            ->willReturn(456);

        $this->app->expects($this->once())
            ->method('getConfig')
            ->with('WELCOME_MAIL_ENABLED')
            ->willReturn('1');

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($mockMedlem);

        $this->email->expects($this->once())
            ->method('send')
            ->with(EmailType::WELCOME, 'john@example.com');

        $this->medlemRepo->expects($this->once())
            ->method('save')
            ->with($mockMedlem);

        $result = $this->service->createPayment($postData);

        $this->assertTrue($result->success);
    }

    public function testDeletePaymentSuccess(): void
    {
        $paymentId = 123;

        $this->betalningRepo->expects($this->once())
            ->method('deleteById')
            ->with($paymentId)
            ->willReturn(true);

        $result = $this->service->deletePayment($paymentId);

        $this->assertTrue($result->success);
        $this->assertEquals('Betalning deleted successfully', $result->message);
    }

    public function testDeletePaymentNotFound(): void
    {
        $paymentId = 999;

        $this->betalningRepo->expects($this->once())
            ->method('deleteById')
            ->with($paymentId)
            ->willReturn(false);

        $result = $this->service->deletePayment($paymentId);

        $this->assertFalse($result->success);
        $this->assertEquals('Payment not found', $result->message);
    }

    public function testDeletePaymentException(): void
    {
        $paymentId = 123;

        $this->betalningRepo->expects($this->once())
            ->method('deleteById')
            ->willThrowException(new Exception('Database error'));

        $result = $this->service->deletePayment($paymentId);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Database error', $result->message);
    }

    private function createMockMedlem(int $id, string $fornamn, string $efternamn): Medlem
    {
        $mock = $this->createMock(Medlem::class);
        $mock->id = $id;
        $mock->fornamn = $fornamn;
        $mock->efternamn = $efternamn;
        return $mock;
    }
}
