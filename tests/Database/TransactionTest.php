<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Database;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;
use Wizaplace\Etl\Database\Transaction;

class TransactionTest extends TestCase
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
        $this->callback = $this->getMockBuilder('stdClass')->addMethods(['callback'])->getMock();

        $this->transaction = new Transaction($this->connection);
    }

    protected function transaction(int $rows): void
    {
        for ($i = 0; $i < $rows; $i++) {
            $this->transaction->run([$this->callback, 'callback']);
        }
    }

    /** @test */
    public function runsSingleTransactionIfSizeIsEmpty(): void
    {
        $this->runCleanly(4, 1);
        $this->transaction->close();
    }

    /** @test */
    public function runsTransactionsWhenCommitSizeIsMultipleOfTotalLines(): void
    {
        $this->transaction->size(3);
        $this->runCleanly(9, 3);
        $this->transaction->close();
    }

    /** @test */
    public function runsTransactionsWhenCommitSizeIsNotMultipleOfTotalLines(): void
    {
        $this->transaction->size(2);
        $this->runCleanly(7, 4);
        $this->transaction->close();
    }

    /** @test */
    public function transactionClosesOnDestroy(): void
    {
        $this->transaction->size(2);
        $this->runCleanly(7, 4);
        unset($this->transaction);
    }

    private function runCleanly(int $calls, int $expectedTransactions): void
    {
        $this->connection->expects(static::exactly($expectedTransactions))->method('beginTransaction');
        $this->connection->expects(static::exactly($expectedTransactions))->method('commit');
        $this->connection->expects(static::exactly(0))->method('rollBack');

        $this->callback->expects(static::exactly($calls))->method('callback');
        $this->transaction($calls);
    }

    /** @test */
    public function rollsBackTheLastTransactionAndStopsExecutionOnError(): void
    {
        $this->callback->expects(static::exactly(3))->method('callback')->willReturnOnConsecutiveCalls(
            null,
            null,
            static::throwException(new Exception())
        );

        $this->connection->expects(static::exactly(2))->method('beginTransaction');
        $this->connection->expects(static::exactly(1))->method('rollBack');
        $this->connection->expects(static::exactly(1))->method('commit');

        $this->transaction->size(2);

        $this->expectException('Exception');

        $this->transaction(4);
    }
}
