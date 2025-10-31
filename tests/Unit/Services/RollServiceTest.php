<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\RollService;
use App\Models\RollRepository;
use App\Models\MedlemRepository;

class RollServiceTest extends TestCase
{
    private RollService $service;
    private $mockRollRepo;
    private $mockMedlemRepo;

    protected function setUp(): void
    {
        $this->mockRollRepo = $this->createMock(RollRepository::class);
        $this->mockMedlemRepo = $this->createMock(MedlemRepository::class);

        $this->service = new RollService(
            $this->mockRollRepo,
            $this->mockMedlemRepo
        );
    }

    public function testGetAllRoles(): void
    {
        $expectedRoles = [
            ['id' => 1, 'roll_namn' => 'Skeppare'],
            ['id' => 2, 'roll_namn' => 'Båtsman']
        ];

        $this->mockRollRepo->expects($this->once())
            ->method('getAll')
            ->willReturn($expectedRoles);

        $result = $this->service->getAllRoles();

        $this->assertEquals($expectedRoles, $result);
    }

    public function testGetMembersInRole(): void
    {
        $expectedMembers = [
            ['id' => 1, 'fornamn' => 'John', 'efternamn' => 'Doe', 'roll_id' => 1]
        ];

        $this->mockMedlemRepo->expects($this->once())
            ->method('getMembersByRollId')
            ->with(1)
            ->willReturn($expectedMembers);

        $result = $this->service->getMembersInRole(1);

        $this->assertEquals($expectedMembers, $result);
    }
}
