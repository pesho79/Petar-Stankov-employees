<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service\Csv;

use App\DTO\EmployeeRecordDto;
use App\Exception\InvalidCsvException;
use App\Service\Csv\CsvReader;
use App\Service\Csv\DateParser;
use App\Service\Csv\EmployeeCsvReader;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EmployeeCsvReaderTest extends TestCase
{
    private EmployeeCsvReader $reader;

    protected function setUp(): void
    {
        $this->reader = new EmployeeCsvReader(
            new CsvReader(),
            new DateParser(),
        );
    }

    public function testReadsEmployeeRecordsFromCsv(): void
    {
        $filePath = $this->createCsvFile([
            [
                'EmpID',
                'ProjectID',
                'DateFrom',
                'DateTo',
            ],
            [
                '143',
                '10',
                '2024-01-01',
                '2024-01-11',
            ],
            [
                '218',
                '10',
                '2024-01-05',
                '2024-01-15',
            ],
        ]);

        try {
            $records = array_values(
                iterator_to_array(
                    $this->reader->read($filePath)
                )
            );

            self::assertCount(2, $records);

            self::assertInstanceOf(
                EmployeeRecordDto::class,
                $records[0]
            );

            self::assertSame(143, $records[0]->employeeId);
            self::assertSame(10, $records[0]->projectId);

            self::assertSame(
                '2024-01-01',
                $records[0]->dateFrom->format('Y-m-d')
            );

            self::assertSame(
                '2024-01-11',
                $records[0]->dateTo->format('Y-m-d')
            );

            self::assertSame(
                218,
                $records[1]->employeeId
            );
        } finally {
            unlink($filePath);
        }
    }

    public function testNullDateToIsConvertedToToday(): void
    {
        $filePath = $this->createCsvFile([
            [
                'EmpID',
                'ProjectID',
                'DateFrom',
                'DateTo',
            ],
            [
                '143',
                '10',
                '2024-01-01',
                'NULL',
            ],
        ]);

        try {
            $records = array_values(
                iterator_to_array(
                    $this->reader->read($filePath)
                )
            );

            self::assertCount(1, $records);

            self::assertSame(
                (new DateTimeImmutable('today'))->format('Y-m-d'),
                $records[0]->dateTo->format('Y-m-d')
            );
        } finally {
            unlink($filePath);
        }
    }

    public function testThrowsExceptionWhenColumnCountIsInvalid(): void
    {
        $filePath = $this->createCsvFile([
            [
                'EmpID',
                'ProjectID',
                'DateFrom',
                'DateTo',
            ],
            [
                '143',
                '10',
                '2024-01-01',
            ],
        ]);

        try {
            $this->expectException(InvalidCsvException::class);

            iterator_to_array(
                $this->reader->read($filePath)
            );
        } finally {
            unlink($filePath);
        }
    }

    public function testThrowsExceptionWhenDateFromIsAfterDateTo(): void
    {
        $filePath = $this->createCsvFile([
            [
                'EmpID',
                'ProjectID',
                'DateFrom',
                'DateTo',
            ],
            [
                '143',
                '10',
                '2024-01-20',
                '2024-01-10',
            ],
        ]);

        try {
            $this->expectException(InvalidCsvException::class);

            iterator_to_array(
                $this->reader->read($filePath)
            );
        } finally {
            unlink($filePath);
        }
    }

    public function testThrowsExceptionForInvalidEmployeeId(): void
    {
        $filePath = $this->createCsvFile([
            [
                'EmpID',
                'ProjectID',
                'DateFrom',
                'DateTo',
            ],
            [
                'abc',
                '10',
                '2024-01-01',
                '2024-01-10',
            ],
        ]);

        try {
            $this->expectException(InvalidCsvException::class);

            iterator_to_array(
                $this->reader->read($filePath)
            );
        } finally {
            unlink($filePath);
        }
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function createCsvFile(array $rows): string
    {
        $filePath = tempnam(
            sys_get_temp_dir(),
            'employees_'
        );

        self::assertNotFalse($filePath);

        $handle = fopen($filePath, 'wb');

        self::assertNotFalse($handle);

        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }

        fclose($handle);

        return $filePath;
    }
}
