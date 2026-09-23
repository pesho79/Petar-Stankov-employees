<?php

declare(strict_types=1);

namespace App\Service\Csv;

use App\DTO\EmployeeRecordDto;
use App\Exception\InvalidCsvException;
use Generator;

final class EmployeeCsvReader
{
    private const COLUMN_COUNT = 4;

    /**
     * @param \App\Service\Csv\CsvReader  $csvReader
     * @param \App\Service\Csv\DateParser $dateParser
     */
    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly DateParser $dateParser,
    ) {
    }

    /**
     * @param string $filePath
     *
     * @return Generator<int, EmployeeRecordDto>
     */
    public function read(string $filePath): Generator
    {
        foreach ($this->csvReader->read($filePath) as $lineNumber => $row) {
            yield $lineNumber => $this->parseRow($row, $lineNumber);
        }
    }

    /**
     * @param array<int, string|null> $row
     * @param int   $lineNumber
     *
     * @return \App\DTO\EmployeeRecordDto
     */
    private function parseRow(array $row, int $lineNumber): EmployeeRecordDto
    {
        if (count($row) !== self::COLUMN_COUNT) {
            throw new InvalidCsvException(
                sprintf(
                    'Invalid number of columns on line %d. Expected %d columns.',
                    $lineNumber,
                    self::COLUMN_COUNT
                )
            );
        }

        $employeeId = $this->parsePositiveInteger(
            $row[0],
            'EmpID',
            $lineNumber
        );

        $projectId = $this->parsePositiveInteger(
            $row[1],
            'ProjectID',
            $lineNumber
        );

        $dateFrom = $this->dateParser->parse($row[2]);
        $dateTo = $this->dateParser->parse($row[3]);

        if ($dateFrom > $dateTo) {
            throw new InvalidCsvException(
                sprintf(
                    'DateFrom cannot be after DateTo on line %d.',
                    $lineNumber
                )
            );
        }

        return new EmployeeRecordDto(
            employeeId: $employeeId,
            projectId: $projectId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );
    }

    /**
     * @param string|null $value
     * @param string      $field
     * @param int         $lineNumber
     *
     * @return int
     */
    private function parsePositiveInteger(
        ?string $value,
        string $field,
        int $lineNumber,
    ): int {
        $value = trim((string) $value);

        if ($value === '' || !ctype_digit($value) || (int) $value <= 0) {
            throw new InvalidCsvException(
                sprintf(
                    'Invalid %s on line %d.',
                    $field,
                    $lineNumber
                )
            );
        }

        return (int) $value;
    }
}
