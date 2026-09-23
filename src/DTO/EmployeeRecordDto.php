<?php

declare(strict_types=1);

namespace App\DTO;

use DateTimeImmutable;

final class EmployeeRecordDto
{
    /**
     * @param int                $employeeId
     * @param int                $projectId
     * @param \DateTimeImmutable $dateFrom
     * @param \DateTimeImmutable $dateTo
     */
    public function __construct(
        public int $employeeId,
        public int $projectId,
        public DateTimeImmutable $dateFrom,
        public DateTimeImmutable $dateTo
    ) { }
}
