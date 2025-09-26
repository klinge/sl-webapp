<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\WebhookController;
use App\Application;
use App\Services\Github\GitHubService;
use App\Services\Github\GitRepositoryService;
use App\Services\Github\DeploymentService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Monolog\Logger;

class WebhookControllerTest extends TestCase
{
    private $app;
    private $request;
    private $logger;
    private $githubService;
    private $gitRepositoryService;
    private $deploymentService;
    private $controller;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->githubService = $this->createMock(GitHubService::class);
        $this->gitRepositoryService = $this->createMock(GitRepositoryService::class);
        $this->deploymentService = $this->createMock(DeploymentService::class);

        // Mock server params for remote IP
        $this->request->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '192.168.1.1']);

        $this->controller = $this->createMockedController();
    }

    private function createMockedController(array $methods = ['jsonResponse']): WebhookController
    {
        $controller = $this->getMockBuilder(WebhookController::class)
            ->setConstructorArgs([
                $this->app,
                $this->request,
                $this->logger,
                $this->githubService,
                $this->gitRepositoryService,
                $this->deploymentService
            ])
            ->onlyMethods($methods)
            ->getMock();

        $controller->method('jsonResponse')->willReturn($this->createMock(ResponseInterface::class));
        return $controller;
    }

    public function testHandleSuccessfulDeployment(): void
    {
        // Mock valid webhook payload
        $payload = [
            'ref' => 'refs/heads/release/v1.0',
            'repository' => [
                'clone_url' => 'https://github.com/user/repo.git',
                'full_name' => 'user/repo'
            ]
        ];

        // Mock verifyRequest to return valid payload
        $this->mockVerifyRequest($payload);

        // Mock GitHub service calls
        $this->githubService->expects($this->once())
            ->method('extractBranchName')
            ->with('refs/heads/release/v1.0')
            ->willReturn('release/v1.0');

        $this->githubService->expects($this->once())
            ->method('isReleaseBranch')
            ->with('release/v1.0')
            ->willReturn(true);

        // Mock successful repository operations
        $this->gitRepositoryService->expects($this->once())
            ->method('updateRepository')
            ->with('release/v1.0', 'https://github.com/user/repo.git')
            ->willReturn(['status' => 'success', 'message' => 'Repository updated']);

        // Mock successful deployment scheduling
        $this->deploymentService->expects($this->once())
            ->method('scheduleDeployment')
            ->willReturn(true);

        $response = $this->controller->handle();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testHandleNonReleaseBranch(): void
    {
        // Mock payload with non-release branch
        $payload = [
            'ref' => 'refs/heads/main',
            'repository' => [
                'clone_url' => 'https://github.com/user/repo.git',
                'full_name' => 'user/repo'
            ]
        ];

        $this->mockVerifyRequest($payload);

        $this->githubService->expects($this->once())
            ->method('extractBranchName')
            ->with('refs/heads/main')
            ->willReturn('main');

        $this->githubService->expects($this->once())
            ->method('isReleaseBranch')
            ->with('main')
            ->willReturn(false);

        // Should not call repository or deployment services
        $this->gitRepositoryService->expects($this->never())
            ->method('updateRepository');

        $this->deploymentService->expects($this->never())
            ->method('scheduleDeployment');

        $response = $this->controller->handle();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testHandleRepositoryOperationFailure(): void
    {
        $payload = [
            'ref' => 'refs/heads/release/v1.0',
            'repository' => [
                'clone_url' => 'https://github.com/user/repo.git',
                'full_name' => 'user/repo'
            ]
        ];

        $this->mockVerifyRequest($payload);

        $this->githubService->method('extractBranchName')->willReturn('release/v1.0');
        $this->githubService->method('isReleaseBranch')->willReturn(true);

        // Mock failed repository operations
        $this->gitRepositoryService->expects($this->once())
            ->method('updateRepository')
            ->willReturn(['status' => 'error', 'message' => 'Git operation failed']);

        // Should not call deployment service on failure
        $this->deploymentService->expects($this->never())
            ->method('scheduleDeployment');

        $response = $this->controller->handle();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testHandleDeploymentSchedulingFailure(): void
    {
        $payload = [
            'ref' => 'refs/heads/release/v1.0',
            'repository' => [
                'clone_url' => 'https://github.com/user/repo.git',
                'full_name' => 'user/repo'
            ]
        ];

        $this->mockVerifyRequest($payload);

        $this->githubService->method('extractBranchName')->willReturn('release/v1.0');
        $this->githubService->method('isReleaseBranch')->willReturn(true);

        $this->gitRepositoryService->method('updateRepository')
            ->willReturn(['status' => 'success', 'message' => 'Repository updated']);

        // Mock failed deployment scheduling
        $this->deploymentService->expects($this->once())
            ->method('scheduleDeployment')
            ->willReturn(false);

        $response = $this->controller->handle();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testVerifyRequestMissingEventHeader(): void
    {
        $this->request->expects($this->once())
            ->method('hasHeader')
            ->with('X-GitHub-Event')
            ->willReturn(false);

        $result = $this->controller->verifyRequest();

        $this->assertEmpty($result);
    }

    public function testVerifyRequestUnsupportedEvent(): void
    {
        $this->request->method('hasHeader')
            ->willReturnMap([
                ['X-GitHub-Event', true],
                ['X-Hub-Signature-256', true]
            ]);

        $this->request->method('getHeader')
            ->willReturnMap([
                ['X-GitHub-Event', ['pull_request']],
                ['X-Hub-Signature-256', ['sha256=validhash']]
            ]);

        $result = $this->controller->verifyRequest();

        $this->assertEmpty($result);
    }

    public function testVerifyRequestMissingSignatureHeader(): void
    {
        $this->request->method('hasHeader')
            ->willReturnMap([
                ['X-GitHub-Event', true],
                ['X-Hub-Signature-256', false]
            ]);

        $this->request->method('getHeader')
            ->with('X-GitHub-Event')
            ->willReturn(['push']);

        $result = $this->controller->verifyRequest();

        $this->assertEmpty($result);
    }

    public function testVerifyRequestInvalidSignatureFormat(): void
    {
        $this->request->method('hasHeader')
            ->willReturnMap([
                ['X-GitHub-Event', true],
                ['X-Hub-Signature-256', true]
            ]);

        $this->request->method('getHeader')
            ->willReturnMap([
                ['X-GitHub-Event', ['push']],
                ['X-Hub-Signature-256', ['invalidformat']]
            ]);

        $result = $this->controller->verifyRequest();

        $this->assertEmpty($result);
    }

    public function testVerifyRequestInvalidSignature(): void
    {
        $this->setupValidHeaders();

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('{"test": "payload"}');
        $this->request->method('getBody')->willReturn($body);

        // Mock invalid signature
        $this->githubService->expects($this->once())
            ->method('validateSignature')
            ->with('{"test": "payload"}', 'validhash')
            ->willReturn(false);

        $result = $this->controller->verifyRequest();

        $this->assertEmpty($result);
    }

    public function testVerifyRequestInvalidRepository(): void
    {
        $this->setupValidHeaders();
        $this->setupValidSignature();

        $payload = ['repository' => ['id' => 999999, 'full_name' => 'wrong/repo']];
        $this->setupRequestBody(json_encode($payload));

        // Mock invalid repository
        $this->githubService->expects($this->once())
            ->method('isValidRepository')
            ->with($payload)
            ->willReturn(false);

        $result = $this->controller->verifyRequest();

        $this->assertEmpty($result);
    }

    public function testVerifyRequestPingEvent(): void
    {
        $this->request->method('hasHeader')
            ->willReturnMap([
                ['X-GitHub-Event', true],
                ['X-Hub-Signature-256', true]
            ]);

        $this->request->method('getHeader')
            ->willReturnMap([
                ['X-GitHub-Event', ['ping']],
                ['X-Hub-Signature-256', ['sha256=validhash']]
            ]);

        $this->setupValidSignature();
        $payload = ['repository' => ['id' => 781366756, 'full_name' => 'user/repo']];
        $this->setupRequestBody(json_encode($payload));

        $this->githubService->method('isValidRepository')->willReturn(true);

        $result = $this->controller->verifyRequest();

        $this->assertEmpty($result);
    }

    public function testVerifyRequestValidPushEvent(): void
    {
        $this->setupValidHeaders();
        $this->setupValidSignature();

        $payload = ['repository' => ['id' => 781366756, 'full_name' => 'user/repo']];
        $this->setupRequestBody(json_encode($payload));

        $this->githubService->method('isValidRepository')->willReturn(true);

        $result = $this->controller->verifyRequest();

        $this->assertEquals($payload, $result);
    }

    private function mockVerifyRequest(array $payload): void
    {
        $this->controller = $this->createMockedController(['verifyRequest', 'jsonResponse']);
        $this->controller->method('verifyRequest')->willReturn($payload);
    }

    private function setupValidHeaders(): void
    {
        $this->request->method('hasHeader')
            ->willReturnMap([
                ['X-GitHub-Event', true],
                ['X-Hub-Signature-256', true]
            ]);

        $this->request->method('getHeader')
            ->willReturnMap([
                ['X-GitHub-Event', ['push']],
                ['X-Hub-Signature-256', ['sha256=validhash']]
            ]);
    }

    private function setupValidSignature(): void
    {
        $this->githubService->method('validateSignature')
            ->willReturn(true);
    }

    private function setupRequestBody(string $body): void
    {
        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($body);
        $this->request->method('getBody')->willReturn($bodyStream);
    }
}
