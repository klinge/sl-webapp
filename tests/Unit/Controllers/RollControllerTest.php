<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\RollController;
use App\Application;
use App\Models\Roll;
use App\Models\MedlemRepository;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

class RollControllerTest extends TestCase
{
    private $app;
    private $request;
    private $logger;
    private $view;
    private $roll;
    private $medlemRepo;
    private $controller;

    protected function setUp(): void
    {
        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->view = $this->createMock(View::class);
        $this->roll = $this->createMock(Roll::class);
        $this->medlemRepo = $this->createMock(MedlemRepository::class);

        $this->controller = new RollController(
            $this->app,
            $this->request,
            $this->logger,
            $this->view,
            $this->roll,
            $this->medlemRepo
        );
    }

    public function testListRendersViewWithRoles(): void
    {
        $expectedRoles = [
            ['id' => 1, 'namn' => 'Skeppare'],
            ['id' => 2, 'namn' => 'Båtsman'],
            ['id' => 3, 'namn' => 'Kock']
        ];

        $this->roll->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedRoles);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->view->expects($this->once())
            ->method('render')
            ->with('viewRoller', [
                'title' => 'Visa roller',
                'items' => $expectedRoles
            ])
            ->willReturn($mockResponse);

        $result = $this->controller->list();
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testMembersInRoleReturnsJsonResponse(): void
    {
        $rollId = 1;
        $expectedMembers = [
            ['id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe'],
            ['id' => 2, 'fornamn' => 'Jane', 'efternamn' => 'Smith']
        ];

        $this->medlemRepo->expects($this->once())
            ->method('getMembersByRollId')
            ->with($rollId)
            ->willReturn($expectedMembers);

        $result = $this->controller->membersInRole($this->request, ['id' => (string) $rollId]);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testMembersInRoleWithStringId(): void
    {
        $rollId = '2';
        $expectedMembers = [
            ['id' => 3, 'fornamn' => 'Bob', 'efternamn' => 'Wilson']
        ];

        $this->medlemRepo->expects($this->once())
            ->method('getMembersByRollId')
            ->with(2)
            ->willReturn($expectedMembers);

        $result = $this->controller->membersInRole($this->request, ['id' => $rollId]);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testMembersInRoleWithEmptyResult(): void
    {
        $rollId = 999;
        $expectedMembers = [];

        $this->medlemRepo->expects($this->once())
            ->method('getMembersByRollId')
            ->with($rollId)
            ->willReturn($expectedMembers);

        $result = $this->controller->membersInRole($this->request, ['id' => (string) $rollId]);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
