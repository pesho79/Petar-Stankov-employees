<?php
declare(strict_types=1);

namespace App\Service;

use App\DTO\EmployeeRecordDto;
use DateTimeImmutable;

final class EmployeeCollaborationService
{
    /**
     * @param iterable<EmployeeRecordDto> $records
     *
     * @return array<int, array{
     *     employee1: int,
     *     employee2: int,
     *     projectId: int,
     *     days: int,
     *     totalDays: int
     * }>
     */
    public function calculate(iterable $records): array
    {
        $projects = [];

        foreach ($records as $record) {
            $projects[$record->projectId][$record->employeeId][] = [
                'from' => $record->dateFrom,
                'to' => $record->dateTo,
            ];
        }

        $pairTotals = [];

        foreach ($projects as $projectId => $employees) {
            if (count($employees) < 2) {
                continue;
            }

            foreach ($employees as $employeeId => $ranges) {
                $employees[$employeeId] = $this->mergeRanges($ranges);
            }

            $employeeIds = array_keys($employees);
            $employeeCount = count($employeeIds);

            for ($i = 0; $i < $employeeCount - 1; ++$i) {
                for ($j = $i + 1; $j < $employeeCount; ++$j) {
                    $employee1 = (int) $employeeIds[$i];
                    $employee2 = (int) $employeeIds[$j];

                    $days = $this->calculateOverlapDays(
                        $employees[$employee1],
                        $employees[$employee2]
                    );

                    if ($days <= 0) {
                        continue;
                    }

                    [$employee1, $employee2] = $this->normalizePair(
                        $employee1,
                        $employee2
                    );

                    $pairKey = sprintf('%d:%d', $employee1, $employee2);

                    if (!isset($pairTotals[$pairKey])) {
                        $pairTotals[$pairKey] = [
                            'employee1' => $employee1,
                            'employee2' => $employee2,
                            'days' => 0,
                            'projects' => [],
                        ];
                    }

                    $pairTotals[$pairKey]['days'] += $days;

                    $pairTotals[$pairKey]['projects'][$projectId] = $days;
                }
            }
        }

        if ($pairTotals === []) {
            return [];
        }

        $maxDays = max(
            array_column($pairTotals, 'days')
        );

        $results = [];

        foreach ($pairTotals as $pair) {
            if ($pair['days'] !== $maxDays) {
                continue;
            }

            foreach ($pair['projects'] as $projectId => $days) {
                $results[] = [
                    'employee1' => $pair['employee1'],
                    'employee2' => $pair['employee2'],
                    'projectId' => (int) $projectId,
                    'days' => $days,
                    'totalDays' => $pair['days'],
                ];
            }
        }

        usort(
            $results,
            static function (array $first, array $second): int {
                return [
                           $first['employee1'],
                           $first['employee2'],
                           $first['projectId'],
                       ] <=> [
                           $second['employee1'],
                           $second['employee2'],
                           $second['projectId'],
                       ];
            }
        );

        return $results;
    }

    /**
     * @param array<int, array{
     *     from: DateTimeImmutable,
     *     to: DateTimeImmutable
     * }> $ranges
     *
     * @return array<int, array{
     *     from: DateTimeImmutable,
     *     to: DateTimeImmutable
     * }>
     */
    private function mergeRanges(array $ranges): array
    {
        usort(
            $ranges,
            static function (array $first, array $second): int {
                return $first['from'] <=> $second['from'];
            }
        );

        $merged = [];

        foreach ($ranges as $range) {
            if ($merged === []) {
                $merged[] = $range;
                continue;
            }

            $lastIndex = count($merged) - 1;
            $last = $merged[$lastIndex];

            if ($range['from'] <= $last['to']) {
                if ($range['to'] > $last['to']) {
                    $merged[$lastIndex]['to'] = $range['to'];
                }

                continue;
            }

            $merged[] = $range;
        }

        return $merged;
    }

    /**
     * @param array<int, array{
     *     from: DateTimeImmutable,
     *     to: DateTimeImmutable
     * }> $firstRanges
     * @param array<int, array{
     *     from: DateTimeImmutable,
     *     to: DateTimeImmutable
     * }> $secondRanges
     */
    private function calculateOverlapDays(
        array $firstRanges,
        array $secondRanges
    ): int {
        $firstIndex = 0;
        $secondIndex = 0;
        $totalDays = 0;

        while (
            $firstIndex < count($firstRanges)
            && $secondIndex < count($secondRanges)
        ) {
            $first = $firstRanges[$firstIndex];
            $second = $secondRanges[$secondIndex];

            $overlapFrom = max($first['from'], $second['from']);
            $overlapTo = min($first['to'], $second['to']);

            if ($overlapFrom < $overlapTo) {
                $totalDays += $overlapFrom
                    ->diff($overlapTo)
                    ->days;
            }

            if ($first['to'] < $second['to']) {
                ++$firstIndex;
            } else {
                ++$secondIndex;
            }
        }

        return $totalDays;
    }

    /**
     * @param int $employee1
     * @param int $employee2
     *
     * @return array{0: int, 1: int}
     */
    private function normalizePair(
        int $employee1,
        int $employee2
    ): array {
        if ($employee1 < $employee2) {
            return [$employee1, $employee2];
        }

        return [$employee2, $employee1];
    }
}
