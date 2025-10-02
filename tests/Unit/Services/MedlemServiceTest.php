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
use App\Utils\Session;
use Monolog\Logger;
use Exception;

class MedlemServiceTest extends TestCase
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

    public function testGetAllMembers(): void
    {
        $expectedMembers = [
            $this->createMockMedlem(1, 'John', 'Doe'),
            $this->createMockMedlem(2, 'Jane', 'Smith')
        ];

        $this->medlemRepo->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedMembers);

        $result = $this->service->getAllMembers();

        $this->assertEquals($expectedMembers, $result);
    }

    public function testGetMemberEditDataSuccess(): void
    {
        $memberId = 1;
        $mockMedlem = $this->createMockMedlem(1, 'John', 'Doe');
        $expectedRoles = [['id' => 1, 'name' => 'Admin']];
        $expectedBetalningar = [['id' => 1, 'amount' => 100]];
        $expectedSeglingar = ['segling1', 'segling2'];

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn($mockMedlem);

        $this->rollRepo->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedRoles);

        $mockMedlem->expects($this->once())
            ->method('getSeglingar')
            ->willReturn($expectedSeglingar);

        $this->betalningRepo->expects($this->once())
            ->method('getBetalningForMedlem')
            ->with($memberId)
            ->willReturn($expectedBetalningar);

        $result = $this->service->getMemberEditData($memberId);

        $this->assertArrayHasKey('medlem', $result);
        $this->assertArrayHasKey('roller', $result);
        $this->assertArrayHasKey('seglingar', $result);
        $this->assertArrayHasKey('betalningar', $result);
        $this->assertEquals($mockMedlem, $result['medlem']);
        $this->assertEquals($expectedRoles, $result['roller']);
        $this->assertEquals($expectedSeglingar, $result['seglingar']);
        $this->assertEquals($expectedBetalningar, $result['betalningar']);
    }

    public function testGetMemberEditDataMemberNotFound(): void
    {
        $memberId = 999;

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Medlem not found');

        $this->service->getMemberEditData($memberId);
    }

    public function testGetAllRoles(): void
    {
        $expectedRoles = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Member']
        ];

        $this->rollRepo->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedRoles);

        $result = $this->service->getAllRoles();

        $this->assertEquals($expectedRoles, $result);
    }

    public function testUpdateMemberSuccess(): void
    {
        $memberId = 1;
        $postData = ['fornamn' => 'Updated', 'efternamn' => 'Name'];
        $mockMedlem = $this->createMockMedlem(1, 'Updated', 'Name');

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn($mockMedlem);

        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->with($mockMedlem, $postData)
            ->willReturn(true);

        $this->medlemRepo->expects($this->once())
            ->method('save')
            ->with($mockMedlem)
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('hasEmailChanged')
            ->willReturn(false);

        $result = $this->service->updateMember($memberId, $postData);

        $this->assertInstanceOf(MedlemServiceResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('medlem-list', $result->redirectRoute);
        $this->assertStringContainsString('Updated Name uppdaterad', $result->message);
    }

    public function testUpdateMemberNotFound(): void
    {
        $memberId = 999;
        $postData = ['fornamn' => 'Test'];

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn(null);

        $result = $this->service->updateMember($memberId, $postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Medlem not found', $result->message);
        $this->assertEquals('medlem-list', $result->redirectRoute);
    }

    public function testUpdateMemberValidationFailure(): void
    {
        $memberId = 1;
        $postData = ['fornamn' => ''];
        $mockMedlem = $this->createMockMedlem(1, 'John', 'Doe');

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn($mockMedlem);

        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->with($mockMedlem, $postData)
            ->willReturn(false);

        $result = $this->service->updateMember($memberId, $postData);

        $this->assertFalse($result->success);
        $this->assertEquals('', $result->message);
        $this->assertEquals('medlem-edit', $result->redirectRoute);
    }

    public function testUpdateMemberSaveFailure(): void
    {
        $memberId = 1;
        $postData = ['fornamn' => 'Updated'];
        $mockMedlem = $this->createMockMedlem(1, 'Updated', 'Name');

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->willReturn($mockMedlem);

        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->willReturn(true);

        $this->medlemRepo->expects($this->once())
            ->method('save')
            ->willReturn(false);

        $result = $this->service->updateMember($memberId, $postData);

        $this->assertFalse($result->success);
        $this->assertEquals('Kunde inte uppdatera medlem!', $result->message);
        $this->assertEquals('medlem-list', $result->redirectRoute);
    }

    public function testUpdateMemberWithEmailChange(): void
    {
        $memberId = 1;
        $postData = ['email' => 'new@example.com'];
        $mockMedlem = $this->createMockMedlem(1, 'John', 'Doe');

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->willReturn($mockMedlem);

        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->willReturn(true);

        $this->medlemRepo->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('hasEmailChanged')
            ->willReturn(true);

        // Mock email alias update
        $this->app->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap([
                ['SMARTEREMAIL_ENABLED', '1'],
                ['SMARTEREMAIL_ALIASNAME', 'test-alias']
            ]);

        $this->medlemRepo->expects($this->once())
            ->method('getEmailForActiveMembers')
            ->willReturn([['email' => 'test@example.com']]);

        $this->mailAliasService->expects($this->once())
            ->method('updateAlias')
            ->with('test-alias', ['test@example.com']);

        $result = $this->service->updateMember($memberId, $postData);

        $this->assertTrue($result->success);
    }

    public function testCreateMemberSuccess(): void
    {
        $postData = ['fornamn' => 'New', 'efternamn' => 'Member'];
        $mockMedlem = $this->createMockMedlem(null, 'New', 'Member');

        $this->medlemRepo->expects($this->once())
            ->method('createNew')
            ->willReturn($mockMedlem);

        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->with($mockMedlem, $postData)
            ->willReturn(true);

        $this->medlemRepo->expects($this->once())
            ->method('save')
            ->with($mockMedlem)
            ->willReturn(true);

        // Mock email aliases disabled
        $this->app->expects($this->once())
            ->method('getConfig')
            ->with('SMARTEREMAIL_ENABLED')
            ->willReturn('0');

        $result = $this->service->createMember($postData);

        $this->assertTrue($result->success);
        $this->assertEquals('medlem-list', $result->redirectRoute);
        $this->assertStringContainsString('New Member skapad', $result->message);
    }

    public function testCreateMemberValidationFailure(): void
    {
        $postData = ['fornamn' => ''];
        $mockMedlem = $this->createMockMedlem(null, '', '');

        $this->medlemRepo->expects($this->once())
            ->method('createNew')
            ->willReturn($mockMedlem);

        $this->validator->expects($this->once())
            ->method('validateAndPrepare')
            ->willReturn(false);

        $result = $this->service->createMember($postData);

        $this->assertFalse($result->success);
        $this->assertEquals('medlem-new', $result->redirectRoute);
    }

    public function testDeleteMemberSuccess(): void
    {
        $memberId = 1;
        $mockMedlem = $this->createMockMedlem(1, 'John', 'Doe');

        Session::set('user_id', 123);

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn($mockMedlem);

        $this->medlemRepo->expects($this->once())
            ->method('delete')
            ->with($mockMedlem)
            ->willReturn(true);

        $this->app->expects($this->once())
            ->method('getConfig')
            ->with('SMARTEREMAIL_ENABLED')
            ->willReturn('0');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('John Doe borttagen av: 123'));

        $result = $this->service->deleteMember($memberId);

        $this->assertTrue($result->success);
        $this->assertEquals('Medlem borttagen!', $result->message);
        $this->assertEquals('medlem-list', $result->redirectRoute);
    }

    public function testDeleteMemberNotFound(): void
    {
        $memberId = 999;

        $this->medlemRepo->expects($this->once())
            ->method('getById')
            ->with($memberId)
            ->willReturn(null);

        $result = $this->service->deleteMember($memberId);

        $this->assertFalse($result->success);
        $this->assertEquals('Medlem not found', $result->message);
    }

    private function createMockMedlem(?int $id, string $fornamn, string $efternamn): Medlem
    {
        $mock = $this->createMock(Medlem::class);
        if ($id !== null) {
            $mock->id = $id;
        }
        $mock->fornamn = $fornamn;
        $mock->efternamn = $efternamn;
        return $mock;
    }
}
