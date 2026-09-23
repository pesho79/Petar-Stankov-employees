<?php

declare(strict_types=1);

namespace App\Service\Csv;

use App\Exception\InvalidCsvException;
use Generator;
use RuntimeException;

final class CsvReader
{
    private const DELIMITER = ',';
    private const ENCLOSURE = '"';
    private const ESCAPE = '\\';

    /**
     * @param string $filePath
     * @param bool   $hasHeader
     *
     * @return Generator<int, array<int, string|null>>
     */
    public function read(string $filePath, bool $hasHeader = true): Generator
    {
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to open CSV file: "%s".',
                    $filePath
                )
            );
        }

        try {
            $lineNumber = 0;

            if ($hasHeader) {
                $header = fgetcsv($handle, 0, self::DELIMITER, self::ENCLOSURE, self::ESCAPE);
                ++$lineNumber;

                if ($header === false) {
                    throw new InvalidCsvException('CSV file is empty.');
                }
            }

            while (($row = fgetcsv($handle, 0, self::DELIMITER, self::ENCLOSURE, self::ESCAPE)) !== false) {
                ++$lineNumber;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                yield $lineNumber => $row;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
