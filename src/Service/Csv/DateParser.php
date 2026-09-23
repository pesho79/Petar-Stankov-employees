<?php
declare(strict_types=1);

namespace App\Service\Csv;

use App\Exception\InvalidCsvException;
use DateTimeImmutable;

final class DateParser
{
    private const FORMATS = [
        'Y-m-d',
        'Y/m/d',
        'Y.m.d',
        'd-m-Y',
        'd/m/Y',
        'd.m.Y',
        'm-d-Y',
        'm/d/Y',
    ];

    /**
     * @param string|null $value
     *
     * @return \DateTimeImmutable
     */
    public function parse(?string $value): DateTimeImmutable
    {
        $value = trim((string) $value);

        if ($value === '' || strtoupper($value) === 'NULL') {
            return new DateTimeImmutable('today');
        }

        foreach (self::FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat(
                '!' . $format,
                $value
            );

            $errors = DateTimeImmutable::getLastErrors();

            $hasErrors = is_array($errors)
                         && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

            if ($date !== false && !$hasErrors && $date->format($format) === $value) {
                return $date;
            }
        }

        throw new InvalidCsvException(
            sprintf('Unsupported date format: "%s".', $value)
        );
    }
}
