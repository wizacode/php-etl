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
use Wizaplace\Etl\Database\ConnectionFactory;
use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Database\Statement;

class StatementTest extends AbstractTestCase
{
    /** @test */
    public function select(): void
    {
        $statement = new Statement($this->createMock('PDO'));
        $statement->select('users');

        static::assertEquals('SELECT * FROM `users`', $statement->toSql());

        $statement = new Statement($this->createMock('PDO'));
        $statement->select('users', ['name', 'email']);

        static::assertEquals('SELECT `name`, `email` FROM `users`', $statement->toSql());
    }

    /** @test */
    public function insert(): void
    {
        $statement = new Statement($this->createMock('PDO'));
        $statement->insert('users', ['name', 'email']);

        static::assertEquals('INSERT INTO `users` (`name`, `email`) values (:name, :email)', $statement->toSql());
    }

    /** @test */
    public function update(): void
    {
        $statement = new Statement($this->createMock('PDO'));
        $statement->update('users', ['name', 'email']);

        static::assertEquals('UPDATE `users` SET `name` = :name, `email` = :email', $statement->toSql());
    }

    /** @test */
    public function delete(): void
    {
        $statement = new Statement($this->createMock('PDO'));
        $statement->delete('users');

        static::assertEquals('DELETE FROM `users`', $statement->toSql());
    }

    /** @test */
    public function where(): void
    {
        $statement = new Statement($this->createMock('PDO'));
        $statement->where(['name', 'email']);

        static::assertEquals('WHERE `name` = :name AND `email` = :email', $statement->toSql());
    }

    /** @test */
    public function prepare(): void
    {
        $pdoStatement = $this->createMock('PDOStatement');

        $pdo = $this->createMock('PDO');
        $pdo->expects(static::once())->method('prepare')->with('')->willReturn($pdoStatement);

        $statement = new Statement($pdo);

        static::assertInstanceOf('PDOStatement', $statement->prepare());
    }

    /** @test */
    public function prepareInvalid(): void
    {
        // Set up connection to SQLite test database.
        $connection = 'default';
        $name = tempnam(sys_get_temp_dir(), 'etl');
        $config = ['driver' => 'sqlite', 'database' => $name];
        $manager = new Manager(new ConnectionFactory());
        $manager->addConnection($config, $connection);

        // Instantiate a table for testing.
        $database = $manager->pdo($connection);
        $database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        $statement = new Statement($database);
        $statement->select('foo', ['>']);

        try {
            $statement->prepare();
            static::fail('An exception should have been thrown');
        } catch (\PDOException $exception) {
            static::assertEquals('SQLSTATE[HY000]: General error: 1 no such table: foo', $exception->getMessage());
        } catch (\Exception $exception) {
            static::fail('An instance of ' . \PDOException::class . ' should have been thrown');
        }
    }
}
