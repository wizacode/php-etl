<?php

declare(strict_types=1);

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

namespace Tests\Usecases;

use PHPUnit\Framework\TestCase;
use Wizaplace\Etl\Etl;
use Wizaplace\Etl\Extractors\Accumulator;
use Wizaplace\Etl\Extractors\Csv;
use Wizaplace\Etl\Transformers\RenameColumns;

class ChainingTest extends TestCase
{
    /** @test */
    public function merging_iterators_chaining()
    {
        // lazy get users
        $usersIterator = (new Etl())
            ->extract(
                new Csv(),
                __DIR__ . '/data/users.csv',
                ['delimiter' => ';']
            )
            ->toIterator();

        // lazy get extended user info
        $infosIterator = (new Etl())
            ->extract(
                new Csv(),
                __DIR__ . '/data/infos.csv',
                ['delimiter' => ';']
            )
            ->transform(
                new RenameColumns(),
                [
                    'columns' => [
                        'courriel' => 'email'
                    ]
                ]
            )
            ->toIterator();

        // and finally feed the Etl this Generator
        $usersInfosIterator = (new Etl())
            ->extract(
                new Accumulator(),
                [
                    $usersIterator,
                    $infosIterator,
                ],
                [
                    'index' => ['email'],
                    'columns' => [
                        'id',
                        'email',
                        'name',
                        'age'
                    ]
                ]
            )
            ->toIterator();

        $expected = [
            [
                'id' => '1',
                'name' => 'John Doe',
                'email' => 'johndoe@email.com',
                'age' => '42',
            ],
            [
                'id' => '2',
                'name' => 'Jane Doe',
                'email' => 'janedoe@email.com',
                'age' => '39',
            ],
        ];

        $actual = iterator_to_array(
            $usersInfosIterator
        );

        static::assertSame(
            $expected,
            $actual
        );
    }

    /**
     * @return mixed[]
     */
    private function getByKey(
        array &$data,
        $key,
        $needle
    ): array {
        // search for email in users
        $id = array_search(
            $needle,
            array_column($data, $key)
        );

        if (false === $id) {
            throw new \Exception('Not found');
        }

        return $data[$id];
    }
}
