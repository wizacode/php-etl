<?php

declare(strict_types=1);

namespace Tests\Unit\Databases;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wizaplace\Etl\Database\Helpers;

class HelpersTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider implodeDataProvider
     */
    public function implode(array $input, string $expected): void
    {
        static::assertEquals(
            $expected,
            Helpers::implode(...$input)
        );
    }

    public static function implodeDataProvider(): array
    {
        return [
            'do not backtick *' => [
                [
                    ['*'],
                    '`{column}`',
                ],
                '*',
            ],
            'do not backtick * in a list' => [
                [
                    ['*', 'other'],
                    '`{column}`',
                ],
                '*, `other`',
            ],
            'backtick * if not in ignoreMask' => [
                [
                    ['*', 'other'],
                    '`{column}`',
                    ['other'],
                ],
                '`*`, other',
            ],
            'common' => [
                [
                    ['hello', 'world'],
                    '#{column}#',
                ],
                '#hello#, #world#',
            ],
            'default' => [
                [
                    ['hello', 'world'],
                ],
                'hello, world',
            ],
        ];
    }
}
