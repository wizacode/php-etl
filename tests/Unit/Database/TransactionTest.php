<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit\Database;

use PHPUnit\Framework\MockObject\MockObject;
use Tests\Tools\AbstractTestCase;
use Tests\Tools\CallbackClass;
use Wizaplace\Etl\Database\ConnectionFactory;
use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Database\Transaction;
use Wizaplace\Etl\Etl;
use Wizaplace\Etl\Extractors\Collection;
use Wizaplace\Etl\Loaders\Insert;

class TransactionTest extends AbstractTestCase
{
    private Transaction $transaction;

    /** @var MockObject|\stdClass */
    private $callback;

    /** @var \PDO|MockObject */
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock('PDO');

        $this->callback = $this->getMockBuilder(CallbackClass::class)->getMock();

        $this->transaction = new Transaction($this->connection);
    }

    protected function transaction(int $rows): void
    {
        for ($i = 0; $i < $rows; $i++) {
            $this->transaction->run([$this->callback, 'callback']);
        }
    }

    public function testRunsSingleTransactionIfSizeIsEmpty(): void
    {
        $this->runCleanly(4, 1);
        $this->transaction->close();
    }

    public function testRunsTransactionsWhenCommitSizeIsMultipleOfTotalLines(): void
    {
        $this->transaction->size(3);
        $this->runCleanly(9, 3);
        $this->transaction->close();
    }

    public function testRunsTransactionsWhenCommitSizeIsNotMultipleOfTotalLines(): void
    {
        $this->transaction->size(2);
        $this->runCleanly(7, 4);
        $this->transaction->close();
    }

    public function testTransactionClosesOnDestroy(): void
    {
        $this->transaction->size(2);
        $this->runCleanly(7, 4);
        unset($this->transaction);
    }

    public function testTransactionClosesOnDestroy2(): void
    {
        $this->transaction->size(0);
        $this->runCleanly(7, 1, 1);
        unset($this->transaction);
    }

    public function testTollsBackLastTransactionAndStopsExecutionOnError(): void
    {
        $this->callback->expects(static::exactly(3))->method('callback')->willReturnOnConsecutiveCalls(
            null,
            null,
            static::throwException(new \Exception())
        );

        $this->connection->expects(static::exactly(1))->method('beginTransaction');
        $this->connection->expects(static::exactly(1))->method('rollBack');
        $this->connection->expects(static::exactly(1))->method('inTransaction')->willReturn(true);
        $this->connection->expects(static::exactly(0))->method('commit');

        $this->transaction->size(0);

        $this->expectException('Exception');

        $this->transaction(4);
    }

    public function testCommitsLastTransactionAndStopsExecutionOnError(): void
    {
        $this->callback->expects(static::exactly(3))->method('callback')->willReturnOnConsecutiveCalls(
            null,
            null,
            static::throwException(new \Exception())
        );

        $this->connection->expects(static::exactly(2))->method('beginTransaction');
        $this->connection->expects(static::exactly(0))->method('rollBack');
        $this->connection->expects(static::exactly(2))->method('inTransaction')->willReturn(true);
        $this->connection->expects(static::exactly(2))->method('commit');

        $this->transaction->size(2);

        $this->expectException('Exception');

        $this->transaction(4);
    }

    /**
     * If an exception is thrown, we should not lose rows that have already been processed.
     */
    public function testTransactionPreservesIngestedRows(): void
    {
        // Set up connection to SQLite test database.
        $connection = 'default';
        $config = ['driver' => 'sqlite', 'database' => ':memory:'];
        $manager = new Manager(new ConnectionFactory());
        $manager->addConnection($config, $connection);

        // Instantiate a table for testing.
        $database = $manager->pdo($connection);
        $table = 'Unit';
        $column = 'value';
        $database->exec("CREATE TABLE $table ($column INT, CHECK ($column < 9))");
        $database->exec("DELETE FROM $table");

        $data = [1, 2, 3, 4, 5, 6, 7, 8, 'zzz'];
        foreach ($data as &$datum) {
            $datum = [$column => $datum];
        }
        $options = [
            'columns' => ['value'],
            'timestamps' => false,
            'transaction' => true,
            'commit_size' => 3,
        ];

        // Perform the insertion. Only the last row, the cause of the exception, should be lost.
        try {
            $pipeline = new Etl();
            $pipeline->extract(new Collection(), new \ArrayIterator($data), [])
                ->load(new Insert($manager), $table, $options)
                ->run();
            static::fail('An exception should have been thrown');
        } catch (\Exception $exception) {
            static::assertEquals(
                [
                    ['value' => 1],
                    ['value' => 2],
                    ['value' => 3],
                    ['value' => 4],
                    ['value' => 5],
                    ['value' => 6],
                    ['value' => 7],
                    ['value' => 8],
                ],
                $database->query("SELECT * FROM $table")->fetchAll(\PDO::FETCH_ASSOC)
            );
            static::assertEquals(8, $database->query("SELECT COUNT(*) FROM $table")->fetchColumn());
        }
    }

    private function runCleanly(int $rows, int $expectedTransactions, int $expectedRollbacks = 0): void
    {
        $expectedCommits = $expectedTransactions - $expectedRollbacks;
        $this->connection->expects(static::exactly($expectedTransactions))->method('beginTransaction');
        $this->connection->expects(static::exactly($expectedTransactions))->method('inTransaction')->willReturn(true);
        $this->connection->expects(static::exactly($expectedCommits))->method('commit');
        $this->connection->expects(static::exactly($expectedRollbacks))->method('rollBack');

        $this->callback->expects(static::exactly($rows))->method('callback');
        $this->transaction($rows);
    }
}
