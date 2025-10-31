<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MedlemService;
use App\Services\MedlemServiceResult;
use App\Services\MedlemDataValidatorService;
use App\Services\MailAliasService;
use App\Models\MedlemRepository;
use App\Models\BetalningRepository;
use App\Models\RollRepository;
use App\Models\Medlem;
use App\Application;
use Monolog\Logger;

class MedlemServiceIntegrationTest extends TestCase
{
    private MedlemService $service;
    private $medlemRepo;
    private $betalningRepo;
    private $rollRepo;
    private $validator;
    private $mailAliasService;
    private $app;
    private $logger;

    protected function setUp(): void
    {
        $this->medlemRepo = $this->createMock(MedlemRepository::class);
        $this->betalningRepo = $this->createMock(BetalningRepository::class);
        $this->rollRepo = $this->createMock(RollRepository::class);
        $this->validator = $this->createMock(MedlemDataValidatorService::class);
        $this->mailAliasService = $this->createMock(MailAliasService::class);
        $this->app = $this->createMock(Application::class);
        $this->logger = $this->createMock(Logger::class);

        $this->service = new MedlemService(
            $this->medlemRepo,
            $this->betalningRepo,
            $this->rollRepo,
            $this->validator,
            $this->mailAliasService,
            $this->app,
            $this->logger
        );
    }

    public function testUpdateMemberWithRepositorySuccess(): void
    {
        // Arrange
        $memberId = 1;
        $postData = ['fornamn' => 'Updated', 'efternamn' => 'Name'];
        $mockMedlem = $this->createMock(Medlem::class);
        $mockMedlem->fornamn = 'Updated';
        $mockMedlem->efternamn = 'Name';

        // Mock repository to return member
        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn($mockMedlem);

        // Mock validator success
        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->with($mockMedlem, $postData)
            ->willReturn(true);

        // Mock repository save success
        $this->medlemRepo->expects($this->once())
            ->method('save')
            ->with($mockMedlem)
            ->willReturn(true);

        // Mock no email change
        $this->validator->expects($this->once())
            ->method('hasEmailChanged')
            ->willReturn(false);

        // Act
        $result = $this->service->updateMember($memberId, $postData);

        // Assert
        $this->assertInstanceOf(MedlemServiceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('medlem-list', $result->redirectRoute);
        $this->assertStringContainsString('Updated Name uppdaterad', $result->message);
    }

    public function testUpdateMemberNotFound(): void
    {
        // Arrange
        $memberId = 999;
        $postData = ['fornamn' => 'Test'];

        // Mock repository to return null (member not found)
        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn(null);

        // Act
        $result = $this->service->updateMember($memberId, $postData);

        // Assert
        $this->assertFalse($result->success);
        $this->assertEquals('Medlem not found', $result->message);
        $this->assertEquals('medlem-list', $result->redirectRoute);
    }

    public function testCreateMemberSuccess(): void
    {
        // Arrange
        $postData = ['fornamn' => 'New', 'efternamn' => 'Member'];
        $mockMedlem = $this->createMock(Medlem::class);
        $mockMedlem->fornamn = 'New';
        $mockMedlem->efternamn = 'Member';

        // Mock repository to create new member
        $this->medlemRepo->expects($this->once())
            ->method('createNew')
            ->willReturn($mockMedlem);

        // Mock validator success
        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->with($mockMedlem, $postData)
            ->willReturn(true);

        // Mock repository save success
        $this->medlemRepo->expects($this->once())
            ->method('save')
            ->with($mockMedlem)
            ->willReturn(true);

        // Mock email aliases disabled
        $this->app->expects($this->once())
            ->method('getConfig')
            ->with('SMARTEREMAIL_ENABLED')
            ->willReturn('0');

        // Act
        $result = $this->service->createMember($postData);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('medlem-list', $result->redirectRoute);
        $this->assertStringContainsString('New Member skapad', $result->message);
    }
}
