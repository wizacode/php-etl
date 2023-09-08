<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit\Extractors;

use PHPUnit\Framework\MockObject\MockObject;
use Tests\Tools\AbstractTestCase;
use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Extractors\Query;
use Wizaplace\Etl\Row;

class QueryTest extends AbstractTestCase
{
    /** @test */
    public function defaultOptions(): void
    {
        /** @var MockObject|\PDOStatement */
        $statement = $this->createMock(\PDOStatement::class);
        $statement
            ->expects(static::once())
            ->method('execute')
            ->with([]);
        $statement
            ->expects(static::exactly(3))
            ->method('fetch')
            ->willReturn(['row1'], ['row2'], null);

        /** @var MockObject|\PDO */
        $connection = $this->createMock(\PDO::class);
        $connection
            ->expects(static::once())
            ->method('prepare')
            ->with('select query')
            ->willReturn($statement);

        /** @var MockObject|Manager */
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects(static::once())
            ->method('pdo')
            ->with('default')
            ->willReturn($connection);

        $extractor = new Query($manager);

        $extractor->input('select query');

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }

    /** @test */
    public function customConnectionAndBindings(): void
    {

        /** @var MockObject|\PDOStatement */
        $statement = $this->createMock(\PDOStatement::class);
        $statement
            ->expects(static::once())
            ->method('execute')
            ->with(['binding']);
        $statement
            ->expects(static::exactly(3))
            ->method('fetch')
            ->willReturn(['row1'], ['row2'], null);

        /** @var MockObject|\PDO */
        $connection = $this->createMock(\PDO::class);
        $connection
            ->expects(static::once())
            ->method('prepare')
            ->with('select query')
            ->willReturn($statement);

        /** @var MockObject|Manager */
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects(static::once())
            ->method('pdo')
            ->with('connection')
            ->willReturn($connection);

        $extractor = new Query($manager);

        $extractor->input('select query');
        $extractor->options([
            $extractor::CONNECTION => 'connection',
            $extractor::BINDINGS => ['binding'],
        ]);

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }
}
