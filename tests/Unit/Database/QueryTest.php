<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit\Database;

use Tests\Tools\AbstractTestCase;
use Wizaplace\Etl\Database\Query;

class QueryTest extends AbstractTestCase
{
    /** @test */
    public function select(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->select('users');

        static::assertEquals('SELECT * FROM `users`', $query->toSql());

        $query = new Query($this->createMock('PDO'));
        $query->select('users', ['name', 'email']);

        static::assertEquals('SELECT `name`, `email` FROM `users`', $query->toSql());
    }

    /** @test */
    public function insert(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->insert('users', ['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);

        static::assertEquals('INSERT INTO `users` (`name`, `email`) VALUES (?, ?)', $query->toSql());
        static::assertEquals(['Jane Doe', 'janedoe@example.com'], $query->getBindings());
    }

    /** @test */
    public function update(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->update('users', ['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);

        static::assertEquals('UPDATE `users` SET `name` = ?, `email` = ?', $query->toSql());
        static::assertEquals(['Jane Doe', 'janedoe@example.com'], $query->getBindings());
    }

    /** @test */
    public function delete(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->delete('users');

        static::assertEquals('DELETE FROM `users`', $query->toSql());
        static::assertEquals([], $query->getBindings());
    }

    /** @test */
    public function where(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->where(['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);

        static::assertEquals('WHERE `name` = ? AND `email` = ?', $query->toSql());
        static::assertEquals(['Jane Doe', 'janedoe@example.com'], $query->getBindings());
    }

    /** @test */
    public function whereIn(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->whereIn('id', ['1', '2']);

        static::assertEquals('WHERE `id` IN (?, ?)', $query->toSql());
        static::assertEquals(['1', '2'], $query->getBindings());
    }

    /** @test */
    public function whereNotIn(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->whereNotIn('id', ['1', '2']);

        static::assertEquals('WHERE `id` NOT IN (?, ?)', $query->toSql());
        static::assertEquals(['1', '2'], $query->getBindings());
    }

    /** @test */
    public function compositeWhereIn(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->whereIn(['id', 'company'], [['id' => '1', 'company' => '1'], ['id' => '2', 'company' => '1']]);

        static::assertEquals('WHERE (`company`, `id`) IN ((?, ?), (?, ?))', $query->toSql());
        static::assertEquals(['1', '1', '1', '2'], $query->getBindings());
    }

    /** @test */
    public function compositeWhereNotIn(): void
    {
        $query = new Query($this->createMock('PDO'));
        $query->whereNotIn(['id', 'company'], [['id' => '1', 'company' => '1'], ['id' => '2', 'company' => '1']]);

        static::assertEquals('WHERE (`company`, `id`) NOT IN ((?, ?), (?, ?))', $query->toSql());
        static::assertEquals(['1', '1', '1', '2'], $query->getBindings());
    }

    /** @test */
    public function executeQuery(): void
    {
        $statement = $this->createMock('PDOStatement');
        $statement->expects(static::once())->method('execute')->with([]);

        $pdo = $this->createMock('PDO');
        $pdo->expects(static::once())->method('prepare')->with('')->willReturn($statement);

        $query = new Query($pdo);

        static::assertInstanceOf('PDOStatement', $query->execute());
    }
}
