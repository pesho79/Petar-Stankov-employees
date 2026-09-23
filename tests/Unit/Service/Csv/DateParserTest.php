<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Csv;

use App\Exception\InvalidCsvException;
use App\Service\Csv\DateParser;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateParserTest extends TestCase
{
    private DateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DateParser();
    }

    #[DataProvider('validDateProvider')]
    public function testParsesSupportedDateFormats(
        string $value,
        string $expected
    ): void {
        $result = $this->parser->parse($value);

        self::assertSame(
            $expected,
            $result->format('Y-m-d')
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validDateProvider(): iterable
    {
        yield 'ISO format' => [
            '2024-01-31',
            '2024-01-31',
        ];

        yield 'ISO slash format' => [
            '2024/01/31',
            '2024-01-31',
        ];

        yield 'ISO dot format' => [
            '2024.01.31',
            '2024-01-31',
        ];

        yield 'European dash format' => [
            '31-01-2024',
            '2024-01-31',
        ];

        yield 'European slash format' => [
            '31/01/2024',
            '2024-01-31',
        ];

        yield 'European dot format' => [
            '31.01.2024',
            '2024-01-31',
        ];

        yield 'US dash format' => [
            '01-31-2024',
            '2024-01-31',
        ];

        yield 'US slash format' => [
            '01/31/2024',
            '2024-01-31',
        ];
    }

    public function testNullReturnsToday(): void
    {
        $result = $this->parser->parse('NULL');

        self::assertSame(
            (new DateTimeImmutable('today'))->format('Y-m-d'),
            $result->format('Y-m-d')
        );
    }

    public function testEmptyValueReturnsToday(): void
    {
        $result = $this->parser->parse('');

        self::assertSame(
            (new DateTimeImmutable('today'))->format('Y-m-d'),
            $result->format('Y-m-d')
        );
    }

    public function testWhitespaceValueReturnsToday(): void
    {
        $result = $this->parser->parse('   ');

        self::assertSame(
            (new DateTimeImmutable('today'))->format('Y-m-d'),
            $result->format('Y-m-d')
        );
    }

    public function testThrowsExceptionForInvalidDate(): void
    {
        $this->expectException(InvalidCsvException::class);

        $this->parser->parse('not-a-date');
    }

    public function testThrowsExceptionForInvalidCalendarDate(): void
    {
        $this->expectException(InvalidCsvException::class);

        $this->parser->parse('2024-02-31');
    }
}
