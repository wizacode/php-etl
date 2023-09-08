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
use Wizaplace\Etl;
use Wizaplace\Etl\Database\ConnectionFactory;
use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Database\Query;
use Wizaplace\Etl\Extractors\Table;
use Wizaplace\Etl\Row;

class TableTest extends AbstractTestCase
{
    /** @test */
    public function defaultOptions(): void
    {
        /** @var MockObject | \PDOStatement */
        $statement = $this->createMock(\PDOStatement::class);
        $statement
            ->expects(static::exactly(3))
            ->method('fetch')
            ->willReturn(['row1'], ['row2'], null);

        /** @var MockObject | Query */
        $query = $this->createMock(Query::class);
        $query
            ->expects(static::once())
            ->method('select')
            ->with('table', ['*'])
            ->willReturnSelf();
        $query
            ->expects(static::once())
            ->method('where')
            ->with([])
            ->willReturnSelf();
        $query
            ->expects(static::once())
            ->method('execute')
            ->willReturn($statement);

        /** @var MockObject | Manager */
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects(static::once())
            ->method('query')
            ->with('default')
            ->willReturn($query);

        $extractor = new Table($manager);

        $extractor->input('table');

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }

    /** @test */
    public function customConnectionColumnsAndWhereClause(): void
    {
        /** @var MockObject | \PDOStatement */
        $statement = $this
            ->createMock(\PDOStatement::class);
        $statement
            ->expects(static::exactly(3))
            ->method('fetch')
            ->willReturn(['row1'], ['row2'], null);

        /** @var MockObject | Query */
        $query = $this->createMock(Query::class);
        $query
            ->expects(static::once())
            ->method('select')
            ->with('table', ['columns'])
            ->willReturnSelf();
        $query
            ->expects(static::once())
            ->method('where')
            ->with(['where'])
            ->willReturnSelf();
        $query
            ->expects(static::once())
            ->method('execute')
            ->willReturn($statement);

        /** @var MockObject | Manager */
        $manager = $this->createMock(Manager::class);
        $manager
            ->expects(static::once())
            ->method('query')
            ->with('connection')
            ->willReturn($query);

        $extractor = new Table($manager);

        $extractor->input('table');
        $extractor->options([
            $extractor::CONNECTION => 'connection',
            $extractor::COLUMNS => ['columns'],
            $extractor::WHERE => ['where'],
        ]);

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }

    /**
     * Tests extended where-clause comparisons (e.g., <>, <, >, <=, >=).
     *
     * @param array           $expected the expected result of table extraction
     * @param string|string[] $where    the where clause used in filtering
     *
     * @test
     *
     * @dataProvider whereClauseDataProvider
     */
    public function whereClauseOperators(array $expected, $where): void
    {
        // Set up connection to SQLite test database.
        $connection = 'default';
        $name = tempnam(sys_get_temp_dir(), 'etl');
        $config = ['driver' => 'sqlite', 'database' => $name];
        $manager = new Manager(new ConnectionFactory());
        $manager->addConnection($config, $connection);

        // Instantiate a table for testing.
        $database = $manager->pdo($connection);
        $table = 'Unit';
        $column = 'column';
        $database->exec("CREATE TABLE $table ($column VARCHAR(20))");
        $database->exec("INSERT INTO $table VALUES ('row1')");
        $database->exec("INSERT INTO $table VALUES ('row2')");

        // Perform the test using data provider arrays for where condition and expected result.
        $pipeline = new Etl\Etl();
        $options = [
            Table::CONNECTION => 'default',
            Table::COLUMNS => [$column],
            Table::WHERE => [$column => $where],
        ];
        $actual = $pipeline->extract(new Table($manager), $table, $options)->toArray();
        self::assertEquals($expected, $actual);

        // Clean up our temporary database.
        unlink($name);
    }

    /**
     * Provides test case scenarios for {@see whereClauseOperators()}.
     */
    public static function whereClauseDataProvider(): array
    {
        return [
            [[], ['<', 'row1']],
            [[['column' => 'row1']], 'row1'],
            [[['column' => 'row1']], ['=', 'row1']],
            [[['column' => 'row2']], ['>', 'row1']],
            [[['column' => 'row2']], ['>=', 'row2']],
            [[['column' => 'row1']], ['<>', 'row2']],
            [[['column' => 'row1'], ['column' => 'row2']], ['<=', 'row2']],
            [[['column' => 'row1'], ['column' => 'row2']], ['<>', 'row3']],
        ];
    }
}
