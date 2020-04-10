<?php

declare(strict_types=1);

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

namespace Tests\Extractors;

use Tests\TestCase;
use TRegx\DataProvider\DataProviders;
use Wizaplace\Etl\DirtyRow;
use Wizaplace\Etl\Exception\IncompleteDataException;
use Wizaplace\Etl\Exception\InvalidOptionException;
use Wizaplace\Etl\Extractors\Accumulator;
use Wizaplace\Etl\Row;

class AccumulatorTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider dataSets
     */
    public function missing_required_index_option($dataSet)
    {
        $extractor = new Accumulator();

        $extractor->input($dataSet);

        $this->expectException(InvalidOptionException::class);
        iterator_to_array($extractor->extract());
    }

    /**
     * @test
     *
     * @dataProvider dataSets
     **/
    public function invalid_index_options($dataSet)
    {
        $invalidIndexes = [
            '0', 0, '', [], null, 'hello',
        ];

        foreach ($invalidIndexes as $invalidIndex) {
            $extractor = new Accumulator();

            $extractor
                ->input($dataSet)
                ->options(
                    [
                        'index' => $invalidIndex,
                        'columns' => ['name', 'twitter'],
                        'strict' => false
                    ]
                );

            $this->expectException(InvalidOptionException::class);
            iterator_to_array($extractor->extract());
        }
    }

    /**
     * @test
     *
     * @dataProvider dataSets
     **/
    public function strict_index_matching($dataSet)
    {
        $extractor = new Accumulator();

        $extractor
            ->input($dataSet)
            ->options(
                [
                    'index' => ['email'],
                    'columns' => ['name', 'twitter'],
                    'strict' => true
                ]
            );

        $this->expectException(IncompleteDataException::class);
        iterator_to_array($extractor->extract());
    }

    /**
     * @test
     *
     * @dataProvider dataSets
     **/
    public function unstrict_index_matching($dataSet)
    {
        $extractor = new Accumulator();

        $extractor
            ->input($dataSet)
            ->options(
                [
                    'index' => ['email'],
                    'columns' => ['name', 'twitter'],
                    'strict' => false
                ]
            );

        $actual = iterator_to_array($extractor->extract());
        $expected = [
            new Row([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'johndoe@email.com',
                'twitter' => '@john',
            ]),
            new Row([
                'id' => 2,
                'name' => 'Jane Doe',
                'email' => 'janedoe@email.com',
                'twitter' => '@jane',
            ]),
            new DirtyRow([
                'id' => 3,
                'name' => 'Incomplete',
                'email' => 'incomplete@dirtydata',
            ]),
        ];
        static::assertEquals($expected, $actual);
    }

    public function dataSets(): array
    {
        return  [
            [
                [
                    $this->array_to_iterator([
                        ['id' => 1, 'name' => 'John Doe', 'email' => 'johndoe@email.com'],
                        ['impossible error'], // should not happen
                        ['id' => 2, 'name' => 'Jane Doe', 'email' => 'janedoe@email.com'],
                        ['id' => 3, 'name' => 'Incomplete', 'email' => 'incomplete@dirtydata']
                    ]),
                    $this->array_to_iterator([
                        ['email' => 'janedoe@email.com', 'twitter' => '@jane'],
                        ['email' => 'johndoe@email.com', 'twitter' => '@john'],
                        ['impossible error'], // should not happen as well
                    ])
                ]
            ]
        ];
    }

    public function array_to_iterator(array $lines): \Iterator
    {
        foreach ($lines as $line) {
            yield $line;
        }
    }
}
