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
use Wizaplace\Etl;
use Wizaplace\Etl\Database\ConnectionFactory;
use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Extractors\Table;
use Wizaplace\Etl\Row;

class TableTest extends TestCase
{
    /** @test */
    public function default_options()
    {
        $statement = $this->createMock('PDOStatement');
        $statement->expects($this->exactly(3))->method('fetch')
            ->will($this->onConsecutiveCalls(['row1'], ['row2'], null));

        $query = $this->createMock('Wizaplace\Etl\Database\Query');
        $query->expects($this->once())->method('select')->with('table', ['*'])->will($this->returnSelf());
        $query->expects($this->once())->method('where')->with([])->will($this->returnSelf());
        $query->expects($this->once())->method('execute')->willReturn($statement);

        $manager = $this->createMock('Wizaplace\Etl\Database\Manager');
        $manager->expects($this->once())->method('query')->with('default')->willReturn($query);

        $extractor = new Table($manager);

        $extractor->input('table');

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }

    /** @test */
    public function custom_connection_columns_and_where_clause()
    {
        $statement = $this->createMock('PDOStatement');
        $statement->expects($this->exactly(3))->method('fetch')
            ->will($this->onConsecutiveCalls(['row1'], ['row2'], null));

        $query = $this->createMock('Wizaplace\Etl\Database\Query');
        $query->expects($this->once())->method('select')->with('table', ['columns'])->will($this->returnSelf());
        $query->expects($this->once())->method('where')->with(['where'])->will($this->returnSelf());
        $query->expects($this->once())->method('execute')->willReturn($statement);

        $manager = $this->createMock('Wizaplace\Etl\Database\Manager');
        $manager->expects($this->once())->method('query')->with('connection')->willReturn($query);

        $extractor = new Table($manager);

        $extractor->input('table');
        $extractor->options([
            'connection' => 'connection',
            'columns' => ['columns'],
            'where' => ['where'],
        ]);

        static::assertEquals([new Row(['row1']), new Row(['row2'])], iterator_to_array($extractor->extract()));
    }

    /**
     * Tests extended where-clause comparisons (e.g., <>, <, >, <=, >=).
     *
     * @param array $expected The expected result of table extraction.
     * @param string|string[] $where The where clause used in filtering.

     * @test
     * @dataProvider whereClauseDataProvider
     */
    public function whereClauseOperators(array $expected, $where)
    {
        $name = tempnam(sys_get_temp_dir(), 'etl');
        $config = [
            'driver' => 'sqlite',
            'database' => $name,
        ];
        $factory = new ConnectionFactory();
        $manager = new Manager($factory);
        $manager->addConnection($config);
        $database = $manager->pdo('default');
        $database->exec('CREATE TABLE unit (column VARCHAR(20))');
        $database->exec('INSERT INTO unit VALUES ("row1")');
        $database->exec('INSERT INTO unit VALUES ("row2")');

        $extractor = new Table($manager);
        $etl = new Etl\Etl();
        $options = [
            'connection' => 'default',
            'columns' => ['column'],
            'where' => ['column' => $where],
        ];
        $array = $etl->extract($extractor, 'unit', $options);
        self::assertEquals($expected, $array->toArray());

        // Clean up our temporary database.
        unlink($name);
    }

    /**
     * Provides test case scenarios for {@see whereClauseOperators()}.
     *
     * @return array A list of test case scenarios.
     */
    public function whereClauseDataProvider(): array
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
