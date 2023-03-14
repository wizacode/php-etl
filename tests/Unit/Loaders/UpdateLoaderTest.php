<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit\Loaders;

use PHPUnit\Framework\MockObject\MockObject;
use Tests\Tools\AbstractTestCase;
use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Database\Statement;
use Wizaplace\Etl\Database\Transaction;
use Wizaplace\Etl\Loaders\Update;
use Wizaplace\Etl\Row;

class UpdateLoaderTest extends AbstractTestCase
{
    /** @var MockObject|Transaction */
    private $transaction;

    /** @var \PDOStatement|MockObject */
    private $insert;

    /** @var \PDOStatement|MockObject */
    private $select;

    /** @var MockObject|Statement */
    private $insertStatement;

    /** @var MockObject|Statement */
    private $selectStatement;

    /** @var \PDOStatement|MockObject */
    private $update;

    /** @var MockObject|Statement */
    private $updateStatement;

    /** @var MockObject|Statement */
    private $statement;

    /** @var MockObject|Manager */
    private $manager;

    /** @var MockObject|Row */
    private $row;

    private Update $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transaction = $this->createMock('Wizaplace\Etl\Database\Transaction');
        $this->transaction->expects(static::any())->method('size')->willReturnSelf();
        $this->transaction->expects(static::any())->method('run')->willReturnCallback(function ($callback): void {
            $callback();
        });
        $this->transaction->expects(static::any())->method('close');

        $this->insert = $this->createMock('PDOStatement');
        $this->insert->expects(static::never())->method('execute');

        $this->insertStatement = $this->createMock('Wizaplace\Etl\Database\Statement');
        $this->insertStatement->expects(static::any())->method('insert')->willReturnSelf();
        $this->insertStatement->expects(static::any())->method('prepare')->willReturn($this->insert);

        $this->select = $this->createMock('PDOStatement');
        $this->select->expects(static::any())->method('execute');

        $this->selectStatement = $this->createMock('Wizaplace\Etl\Database\Statement');
        $this->selectStatement->expects(static::any())->method('select')->willReturnSelf();
        $this->selectStatement->expects(static::any())->method('where')->willReturnSelf();
        $this->selectStatement->expects(static::any())->method('prepare')->willReturn($this->select);

        $this->update = $this->createMock('PDOStatement');
        $this->update->expects(static::any())->method('execute');

        $this->updateStatement = $this->createMock('Wizaplace\Etl\Database\Statement');
        $this->updateStatement->expects(static::any())->method('update')->willReturnSelf();
        $this->updateStatement->expects(static::any())->method('where')->willReturnSelf();
        $this->updateStatement->expects(static::any())->method('prepare')->willReturn($this->update);

        $this->statement = $this->createMock('Wizaplace\Etl\Database\Statement');
        $this->statement->expects(static::any())->method('insert')->willReturn($this->insertStatement);
        $this->statement->expects(static::any())->method('select')->willReturn($this->selectStatement);
        $this->statement->expects(static::any())->method('update')->willReturn($this->updateStatement);

        $this->manager = $this->createMock('Wizaplace\Etl\Database\Manager');
        $this->manager->expects(static::any())->method('statement')->willReturn($this->statement);
        $this->manager->expects(static::any())->method('transaction')->willReturn($this->transaction);

        $this->row = $this->createMock('Wizaplace\Etl\Row');
        $this->row->expects(static::any())->method('toArray')
            ->willReturn(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@example.com']);

        $this->loader = new Update($this->manager);
        $this->loader->output('table');
    }

    /** @test */
    public function updateRowIfFoundInDatabase(): void
    {
        $this->statement->expects(static::once())->method('select')->with('table');
        $this->selectStatement->expects(static::once())->method('where')->with(['id']);
        $this->selectStatement->expects(static::once())->method('prepare');
        $this->select->expects(static::once())->method('execute')->with(['id' => '1']);
        $this->select->expects(static::once())->method('fetch')->willReturn(['name' => 'Jane']);

        $this->statement->expects(static::once())->method('update')->with('table', ['name', 'email']);
        $this->updateStatement->expects(static::once())->method('where')->with(['id']);
        $this->updateStatement->expects(static::once())->method('prepare');
        $this->update->expects(static::once())->method('execute')
            ->with(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@example.com']);

        $this->insert->expects(static::never())->method('execute');

        $this->transaction->expects(static::once())->method('size')->with(0);
        $this->transaction->expects(static::once())->method('run');
        $this->transaction->expects(static::once())->method('close');

        $this->execute($this->loader, [$this->row]);
    }

    /** @test */
    public function doNotUpdateIfThereAreNoChanges(): void
    {
        $this->statement->expects(static::once())->method('select')->with('table');
        $this->selectStatement->expects(static::once())->method('where')->with(['id']);
        $this->selectStatement->expects(static::once())->method('prepare');
        $this->select->expects(static::once())->method('execute')->with(['id' => '1']);
        $this->select->expects(static::once())->method('fetch')->willReturn([
            'id' => '1',
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'created_at' => date('Y-m-d G:i:s'),
            'updated_at' => date('Y-m-d G:i:s'),
        ]);

        $this->statement->expects(static::once())->method('update')->with('table', ['name', 'email']);
        $this->updateStatement->expects(static::once())->method('where')->with(['id']);
        $this->updateStatement->expects(static::once())->method('prepare');
        $this->update->expects(static::never())->method('execute');

        $this->insert->expects(static::never())->method('execute');

        $this->transaction->expects(static::once())->method('size')->with(0);
        $this->transaction->expects(static::once())->method('run');

        $this->execute($this->loader, [$this->row]);
    }

    /** @test */
    public function filteringColumnsToUpdate(): void
    {
        $this->select->expects(static::once())->method('fetch')->willReturn(['name' => 'Jane']);

        $this->statement->expects(static::once())->method('update')->with('table', ['name']);
        $this->update->expects(static::once())->method('execute')->with(['id' => '1',  'name' => 'Jane Doe']);

        $this->loader->options([$this->loader::COLUMNS => ['id', 'name']]);

        $this->execute($this->loader, [$this->row]);
    }

    /** @test */
    public function mappingColumnsToUpdate(): void
    {
        $this->select->expects(static::once())->method('fetch')->willReturn(['name' => 'Jane']);

        $this->statement->expects(static::once())->method('update')->with('table', ['full_name']);
        $this->update->expects(static::once())->method('execute')->with(['id' => '1', 'full_name' => 'Jane Doe']);

        $this->loader->options([$this->loader::COLUMNS => ['id' => 'id', 'name' => 'full_name']]);

        $this->execute($this->loader, [$this->row]);
    }

    /** @test */
    public function updateDataUsingTimestamps(): void
    {
        $this->select->expects(static::once())->method('fetch')->willReturn(['name' => 'Jane']);

        $this->statement->expects(static::once())->method('update')->with('table', ['name', 'email', 'updated_at']);
        $this->update->expects(static::once())->method('execute')->with([
            'id' => '1',
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'updated_at' => date('Y-m-d G:i:s'),
        ]);

        $this->loader->options([$this->loader::TIMESTAMPS => true]);

        $this->execute($this->loader, [$this->row]);
    }

    /** @test */
    public function updateDataIntoDatabaseWithoutTransactions(): void
    {
        $this->transaction->expects(static::never())->method('size');
        $this->transaction->expects(static::never())->method('run');
        $this->manager->expects(static::never())->method('transaction');

        $this->select->expects(static::once())->method('fetch')->willReturn(['name' => 'Jane']);
        $this->update->expects(static::once())->method('execute');

        $this->loader->options([$this->loader::TRANSACTION => false]);

        $this->execute($this->loader, [$this->row]);
    }
}
