<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\RollController;
use App\Services\RollService;
use App\Utils\View;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class RollControllerTest extends TestCase
{
    private RollController $controller;
    private $mockRollService;
    private $mockView;

    protected function setUp(): void
    {
        $this->mockRollService = $this->createMock(RollService::class);
        $this->mockView = $this->createMock(View::class);

        $this->controller = new RollController(
            $this->mockRollService,
            $this->mockView
        );
    }

    public function testListDelegatesServiceAndRendersView(): void
    {
        $expectedRoles = [
            ['id' => 1, 'roll_namn' => 'Skeppare'],
            ['id' => 2, 'roll_namn' => 'Båtsman']
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);

        $this->mockRollService->expects($this->once())
            ->method('getAllRoles')
            ->willReturn($expectedRoles);

        $this->mockView->expects($this->once())
            ->method('render')
            ->with('viewRoller', [
                'title' => 'Visa roller',
                'items' => $expectedRoles
            ])
            ->willReturn($mockResponse);

        $result = $this->controller->list();

        $this->assertSame($mockResponse, $result);
    }

    public function testMembersInRoleDelegatesServiceAndReturnsJson(): void
    {
        $expectedMembers = [
            ['id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe']
        ];

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $params = ['id' => '1'];

        $this->mockRollService->expects($this->once())
            ->method('getMembersInRole')
            ->with(1)
            ->willReturn($expectedMembers);

        $result = $this->controller->membersInRole($mockRequest, $params);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
