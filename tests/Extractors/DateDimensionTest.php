<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Extractors;

use Tests\TestCase;
use Wizaplace\Etl\Extractors\DateDimension;
use Wizaplace\Etl\Row;

class DateDimensionTest extends TestCase
{
    const DAY_AS_SECONDS = 24 * 60 * 60;

    /** @test */
    public function defaultOptions(): void
    {
        $expected = [
            new Row([
                'DateKey' => '20200404',
                'DateFullName' => 'April 4, 2020',
                'DateFull' => '2020-04-04T00:00:00+00:00',
                'Year' => '2020',
                'Quarter' => 2,
                'QuarterName' => 'Q2',
                'QuarterKey' => 2,
                'Month' => '4',
                'MonthKey' => '4',
                'MonthName' => 'April',
                'DayOfMonth' => '4',
                'NumberOfDaysInTheMonth' => '30',
                'DayOfYear' => 95,
                'WeekOfYear' => '14',
                'WeekOfYearKey' => '14',
                'ISOWeek' => '14',
                'ISOWeekKey' => '14',
                'WeekDay' => '6',
                'WeekDayName' => 'Saturday',
                'IsWorkDayKey' => 0,
            ]),
            new Row([
                'DateKey' => '20200405',
                'DateFullName' => 'April 5, 2020',
                'DateFull' => '2020-04-05T00:00:00+00:00',
                'Year' => '2020',
                'Quarter' => 2,
                'QuarterName' => 'Q2',
                'QuarterKey' => 2,
                'Month' => '4',
                'MonthKey' => '4',
                'MonthName' => 'April',
                'DayOfMonth' => '5',
                'NumberOfDaysInTheMonth' => '30',
                'DayOfYear' => 96,
                'WeekOfYear' => '14',
                'WeekOfYearKey' => '14',
                'ISOWeek' => '14',
                'ISOWeekKey' => '14',
                'WeekDay' => '0',
                'WeekDayName' => 'Sunday',
                'IsWorkDayKey' => 0,
            ]),
            new Row([
                'DateKey' => '20200406',
                'DateFullName' => 'April 6, 2020',
                'DateFull' => '2020-04-06T00:00:00+00:00',
                'Year' => '2020',
                'Quarter' => 2,
                'QuarterName' => 'Q2',
                'QuarterKey' => 2,
                'Month' => '4',
                'MonthKey' => '4',
                'MonthName' => 'April',
                'DayOfMonth' => '6',
                'NumberOfDaysInTheMonth' => '30',
                'DayOfYear' => 97,
                'WeekOfYear' => '15',
                'WeekOfYearKey' => '15',
                'ISOWeek' => '15',
                'ISOWeekKey' => '15',
                'WeekDay' => '1',
                'WeekDayName' => 'Monday',
                'IsWorkDayKey' => 1,
            ]),
        ];

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::START_DATE => '2020-04-04T00:00:00+0',
                $extractor::END_DATE => '2020-04-06T00:00:00+0',
            ]
        );
        static::assertEquals($expected, iterator_to_array($extractor->extract()));
    }

    /** @test */
    public function selectedColumns(): void
    {
        $expected = [
            new Row([
                'DateKey' => '20200101',
                'DateFull' => '2020-01-01T06:00:00-04:00',
                'Year' => '2020',
                'Month' => '1',
                'DayOfMonth' => '1',
            ]),
            new Row([
                'DateKey' => '20200102',
                'DateFull' => '2020-01-02T06:00:00-04:00',
                'Year' => '2020',
                'Month' => '1',
                'DayOfMonth' => '2',
            ]),
            new Row([
                'DateKey' => '20200103',
                'DateFull' => '2020-01-03T06:00:00-04:00',
                'Year' => '2020',
                'Month' => '1',
                'DayOfMonth' => '3',
            ]),
        ];

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::START_DATE => '2020-01-01T06:00:00-4',
                $extractor::END_DATE => '2020-01-03T06:00:00-4',
                $extractor::COLUMNS => [
                    $extractor::ROW_DATE_KEY,
                    $extractor::ROW_DATE_FULL,
                    $extractor::ROW_YEAR,
                    $extractor::ROW_MONTH,
                    $extractor::ROW_DAY_OF_MONTH,
                ],
            ]
        );
        static::assertEquals($expected, iterator_to_array($extractor->extract()));
    }

    /** @test */
    public function quarters(): void
    {
        $quarters = array_merge(
            array_fill(0, 31, 1),
            array_fill(0, 28, 1),
            array_fill(0, 31, 1),
            array_fill(0, 30, 2),
            array_fill(0, 31, 2),
            array_fill(0, 30, 2),
            array_fill(0, 31, 3),
            array_fill(0, 31, 3),
            array_fill(0, 30, 3),
            array_fill(0, 31, 4),
            array_fill(0, 30, 4),
            array_fill(0, 31, 4)
        );
        $expected = [];
        foreach ($quarters as $quarter) {
            $expected[] = new Row(['Quarter' => $quarter, 'QuarterName' => "Q$quarter"]);
        }

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::START_DATE => '2021-01-01T06:00:00-4',
                $extractor::END_DATE => '2021-12-31T06:00:00-4',
                $extractor::COLUMNS => [
                    $extractor::ROW_QUARTER,
                    $extractor::ROW_QUARTER_NAME,
                ],
            ]
        );
        static::assertEquals($expected, iterator_to_array($extractor->extract()));
    }

    /** @test */
    public function defaultStart(): void
    {
        date_default_timezone_set('America/New_York');

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::COLUMNS => [
                    $extractor::ROW_DATE_KEY,
                    $extractor::ROW_DATE_FULL,
                ],
            ]
        );

        $firstDay = new \DateTimeImmutable('first day of January');
        $year = (int) $firstDay->format('Y');

        $result = \iterator_to_array($extractor->extract());
        $firstRow = reset($result[0]);
        $lastRow = end($result);

        static::assertStringMatchesFormat(
            '%d-%d-%dT00:00:00-%d:00',
            $firstRow[$extractor::ROW_DATE_FULL]
        );
        static::assertStringMatchesFormat(
            '%d-%d-%dT00:00:00-%d:00',
            $lastRow[$extractor::ROW_DATE_FULL]
        );
        static::assertStringContainsString('12-31', $lastRow[$extractor::ROW_DATE_FULL]);
        static::assertGreaterThan(3650, count($result));
        static::assertEquals($year - 5 . '0101', $firstRow[$extractor::ROW_DATE_KEY]);
        static::assertEquals($year + 4 . '1231', $lastRow[$extractor::ROW_DATE_KEY]);
    }

    /** @test */
    public function handlesDaylightSavingsTenYears(): void
    {
        date_default_timezone_set('America/New_York');

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::START_DATE => '2016-01-01',
                $extractor::END_DATE => '2025-12-31',
                $extractor::COLUMNS => [
                    $extractor::ROW_DATE_KEY,
                    $extractor::ROW_DATE_FULL,
                ],
            ]
        );

        [$days, $longDays, $shortDays, $gainedTime] = $this->iterateDimensions($extractor);
        self::assertEquals(3633, $days);
        self::assertEquals(10, $longDays);
        self::assertEquals(10, $shortDays);
        self::assertEquals(0, $gainedTime);
    }

    /** @test */
    public function handlesDaylightSavingsAYearAndAHalf(): void
    {
        date_default_timezone_set('America/New_York');

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::START_DATE => '2019-01-01',
                $extractor::END_DATE => '2020-07-01',
                $extractor::COLUMNS => [
                    $extractor::ROW_DATE_KEY,
                    $extractor::ROW_DATE_FULL,
                ],
            ]
        );

        [$commonDays, $longDays, $shortDays, $gainedTime] = $this->iterateDimensions($extractor);
        self::assertEquals(545, $commonDays);
        self::assertEquals(1, $longDays);
        self::assertEquals(2, $shortDays);
        self::assertEquals(-3600, $gainedTime);
    }

    /** @test */
    public function handlesDaylightSavingsDefaultStart(): void
    {
        date_default_timezone_set('America/New_York');

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::COLUMNS => [
                    $extractor::ROW_DATE_KEY,
                    $extractor::ROW_DATE_FULL,
                ],
            ]
        );

        [$commonDays, $longDays, $shortDays, $gainedTime] = $this->iterateDimensions($extractor);

        self::assertGreaterThan(3630, $commonDays);
        self::assertEquals(10, $longDays);
        self::assertEquals(10, $shortDays);
        self::assertEquals(0, $gainedTime);
    }

    /** @test */
    public function handlesDaylightSavingsUtc(): void
    {
        date_default_timezone_set('UTC');

        $extractor = new DateDimension();
        $extractor->options(
            [
                $extractor::START_DATE => '2022-01-01',
                $extractor::END_DATE => '2022-12-31',
                $extractor::COLUMNS => [
                    $extractor::ROW_DATE_KEY,
                    $extractor::ROW_DATE_FULL,
                ],
            ]
        );

        [$commonDays, $longDays, $shortDays, $gainedTime] = $this->iterateDimensions($extractor);

        self::assertEquals(365, $commonDays);
        self::assertEquals(0, $longDays);
        self::assertEquals(0, $shortDays);
        self::assertEquals(0, $gainedTime);
    }

    private function iterateDimensions(DateDimension $extractor): array
    {
        $commonDays = 0;
        $shortDays = 0;
        $longDays = 0;

        $gainedTime = 0;
        $previousDayTimestamp = null;
        $delta = 0;

        foreach ($extractor->extract() as $date) {
            $currentDayTimestamp = (new \DateTimeImmutable($date[$extractor::ROW_DATE_FULL]))->getTimestamp();

            if (null !== $previousDayTimestamp) {
                $delta = $currentDayTimestamp - $previousDayTimestamp - static::DAY_AS_SECONDS;
            }

            if ($delta > 0) {
                $longDays++;
                $gainedTime += $delta;
            } elseif ($delta < 0) {
                $shortDays++;
                $gainedTime += $delta;
            } else {
                $commonDays++;
            }

            $previousDayTimestamp = $currentDayTimestamp;
        }

        return [
            $commonDays,
            $longDays,
            $shortDays,
            $gainedTime,
        ];
    }
}
