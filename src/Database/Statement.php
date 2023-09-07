<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

class Statement
{
    /**
     * The database connection.
     */
    protected \PDO $pdo;

    /**
     * The sql query components.
     */
    protected array $query = [];

    /**
     * The where constraints for the query.
     */
    protected array $whereStatements = [];

    /**
     * Create a new Statement instance.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Prepare the statement for execution.
     *
     * @throws \TypeError If SQL cannot be successfully prepared
     */
    public function prepare(): \PDOStatement
    {
        /** @var \PDOStatement|false $statement */
        $statement = $this->pdo->prepare($this->toSql());
        if (!$statement) {
            $error = $this->pdo->errorInfo();
            throw new \PDOException("SQLSTATE[$error[0]]: General error: $error[1] $error[2]");
        }

        return $statement;
    }

    /**
     * Get the sql query string.
     */
    public function toSql(): string
    {
        $this->compileWheres();

        return implode(' ', $this->query);
    }

    /**
     * Select statement.
     *
     * @return $this
     */
    public function select(string $table, ?array $columns = null): Statement
    {
        $columns = \is_null($columns)
            ? '*'
            : Helpers::implode($columns, Helpers::BACKTICKED_MASK);

        $this->query[] = "SELECT $columns FROM `$table`";

        return $this;
    }

    /**
     * Insert statement.
     *
     * @return $this
     */
    public function insert(string $table, array $columns): Statement
    {
        $values = Helpers::implode($columns, ':{column}');

        $columns = Helpers::implode($columns, Helpers::BACKTICKED_MASK);

        $this->query[] = "INSERT INTO `$table` ($columns) values ($values)";

        return $this;
    }

    /**
     * Update statement.
     *
     * @return $this
     */
    public function update(string $table, array $columns): Statement
    {
        $columns = Helpers::implode(
            $columns,
            \sprintf(
                '%s = :%s',
                Helpers::BACKTICKED_MASK,
                Helpers::DEFAULT_MASK,
            )
        );

        $this->query[] = "UPDATE `$table` SET $columns";

        return $this;
    }

    /**
     * Delete statement.
     *
     * @return $this
     */
    public function delete(string $table): Statement
    {
        $this->query[] = "DELETE FROM `$table`";

        return $this;
    }

    /**
     * Where statement.
     *
     * @return $this
     */
    public function where(array $columns): Statement
    {
        foreach ($columns as $column) {
            $this->whereStatements[] = new WhereStatement(
                boolean: WhereBoolean::And,
                operator: WhereOperator::Equal,
                column: $column,
            );
        }

        return $this;
    }

    /**
     * Compile all where statements.
     */
    protected function compileWheres(): void
    {
        if ([] === $this->whereStatements) {
            return;
        }

        $this->query[] = 'WHERE';

        foreach ($this->whereStatements as $index => $whereQuery) {
            $result = $whereQuery->compile($index);

            $this->query[] = $result->output;
        }
    }
}
