<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\EmployeeRecordDto;
use App\Service\EmployeeCollaborationService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EmployeeCollaborationServiceTest extends TestCase
{
    private EmployeeCollaborationService $service;

    protected function setUp(): void
    {
        $this->service = new EmployeeCollaborationService();
    }

    public function testCalculatesOverlapForOneProject(): void
    {
        $records = [
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-05'),
                dateTo: new DateTimeImmutable('2024-01-15'),
            ),
        ];

        $result = $this->service->calculate($records);

        self::assertCount(1, $result);

        self::assertSame(
            [
                'employee1' => 143,
                'employee2' => 218,
                'projectId' => 10,
                'days' => 6,
                'totalDays' => 6,
            ],
            $result[0]
        );
    }

    public function testAggregatesDaysAcrossMultipleProjects(): void
    {
        $records = [
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-05'),
                dateTo: new DateTimeImmutable('2024-01-15'),
            ),
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 20,
                dateFrom: new DateTimeImmutable('2024-02-01'),
                dateTo: new DateTimeImmutable('2024-02-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 20,
                dateFrom: new DateTimeImmutable('2024-02-05'),
                dateTo: new DateTimeImmutable('2024-02-15'),
            ),
        ];

        $result = $this->service->calculate($records);

        self::assertCount(2, $result);

        self::assertSame(143, $result[0]['employee1']);
        self::assertSame(218, $result[0]['employee2']);
        self::assertSame(10, $result[0]['projectId']);
        self::assertSame(6, $result[0]['days']);
        self::assertSame(12, $result[0]['totalDays']);

        self::assertSame(143, $result[1]['employee1']);
        self::assertSame(218, $result[1]['employee2']);
        self::assertSame(20, $result[1]['projectId']);
        self::assertSame(6, $result[1]['days']);
        self::assertSame(12, $result[1]['totalDays']);
    }

    public function testReturnsEmptyResultWhenEmployeesDoNotShareProject(): void
    {
        $records = [
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 20,
                dateFrom: new DateTimeImmutable('2024-01-05'),
                dateTo: new DateTimeImmutable('2024-01-15'),
            ),
        ];

        $result = $this->service->calculate($records);

        self::assertSame([], $result);
    }

    public function testDoesNotCountTouchingDateRangesAsOverlap(): void
    {
        $records = [
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-10'),
            ),
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-10'),
                dateTo: new DateTimeImmutable('2024-01-20'),
            ),
        ];

        $result = $this->service->calculate($records);

        self::assertSame([], $result);
    }

    public function testMergesOverlappingRangesForSameEmployeeAndProject(): void
    {
        $records = [
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-05'),
                dateTo: new DateTimeImmutable('2024-01-15'),
            ),
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-15'),
            ),
        ];

        $result = $this->service->calculate($records);

        self::assertCount(1, $result);
        self::assertSame(14, $result[0]['days']);
        self::assertSame(14, $result[0]['totalDays']);
    }

    public function testReturnsAllPairsWhenThereIsATie(): void
    {
        $records = [
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 303,
                projectId: 20,
                dateFrom: new DateTimeImmutable('2024-02-01'),
                dateTo: new DateTimeImmutable('2024-02-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 404,
                projectId: 20,
                dateFrom: new DateTimeImmutable('2024-02-01'),
                dateTo: new DateTimeImmutable('2024-02-11'),
            ),
        ];

        $result = $this->service->calculate($records);

        self::assertCount(2, $result);

        self::assertSame(143, $result[0]['employee1']);
        self::assertSame(218, $result[0]['employee2']);
        self::assertSame(10, $result[0]['days']);
        self::assertSame(10, $result[0]['totalDays']);

        self::assertSame(303, $result[1]['employee1']);
        self::assertSame(404, $result[1]['employee2']);
        self::assertSame(20, $result[1]['projectId']);
        self::assertSame(10, $result[1]['days']);
        self::assertSame(10, $result[1]['totalDays']);
    }

    public function testNormalizesEmployeePairOrder(): void
    {
        $records = [
            new EmployeeRecordDto(
                employeeId: 218,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
            new EmployeeRecordDto(
                employeeId: 143,
                projectId: 10,
                dateFrom: new DateTimeImmutable('2024-01-01'),
                dateTo: new DateTimeImmutable('2024-01-11'),
            ),
        ];

        $result = $this->service->calculate($records);

        self::assertCount(1, $result);
        self::assertSame(143, $result[0]['employee1']);
        self::assertSame(218, $result[0]['employee2']);
    }

    public function testReturnsEmptyResultForEmptyRecords(): void
    {
        $result = $this->service->calculate([]);

        self::assertSame([], $result);
    }
}
