<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit\Extractors;

use Tests\Tools\AbstractTestCase;
use Wizaplace\Etl\Extractors\Query;
use Wizaplace\Etl\Row;

class QueryTest extends AbstractTestCase
{
    public function testDefaultOptions(): void
    {
        $statement = $this->createMock('PDOStatement');
        $statement->expects(static::once())->method('execute')->with([]);

        $results = [
            ['row1'],
            ['row2'],
            null,
        ];

        $statement->expects(static::exactly(3))
            ->method('fetch')
            ->willReturnCallback(function () use (&$results) {
                return array_shift($results);
            });

        $connection = $this->createMock('PDO');
        $connection->expects(static::once())->method('prepare')->with('select query')->willReturn($statement);

        $manager = $this->createMock('Wizaplace\Etl\Database\Manager');
        $manager->expects(static::once())->method('pdo')->with('default')->willReturn($connection);

        $extractor = new Query($manager);

        $extractor->input('select query');

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }

    public function testCustomConnectionAndBindings(): void
    {
        $statement = $this->createMock('PDOStatement');
        $statement->expects(static::once())->method('execute')->with(['binding']);

        $results = [
            ['row1'],
            ['row2'],
            null,
        ];

        $statement->expects(static::exactly(3))
            ->method('fetch')
            ->willReturnCallback(function () use (&$results) {
                return array_shift($results);
            });

        $connection = $this->createMock('PDO');
        $connection->expects(static::once())->method('prepare')->with('select query')->willReturn($statement);

        $manager = $this->createMock('Wizaplace\Etl\Database\Manager');
        $manager->expects(static::once())->method('pdo')->with('connection')->willReturn($connection);

        $extractor = new Query($manager);

        $extractor->input('select query');
        $extractor->options([
            $extractor::CONNECTION => 'connection',
            $extractor::BINDINGS => ['binding'],
        ]);

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }
}
