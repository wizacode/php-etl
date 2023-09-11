<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

class Query
{
    /**
     * The database connection.
     */
    protected \PDO $pdo;

    /**
     * The bindings for the query.
     */
    protected array $bindings = [];

    /**
     * The sql query components.
     */
    protected array $query = [];

    /**
     * The where constraints for the query.
     *
     * @var WhereInterface[]
     */
    protected array $whereQueries = [];

    /**
     * Create a new Query instance.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Execute the query.
     */
    public function execute(): \PDOStatement
    {
        $statement = $this->pdo->prepare($this->toSql());

        $statement->execute($this->bindings);

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
     * Get the query bindings.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Select statement.
     *
     * @return $this
     */
    public function select(string $table, array $columns = null): Query
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
    public function insert(string $table, array $columns): Query
    {
        $this->bindings = array_merge($this->bindings, array_values($columns));

        $values = Helpers::implode($columns, '?');

        $columns = Helpers::implode(array_keys($columns), Helpers::BACKTICKED_MASK);

        $this->query[] = "INSERT INTO `$table` ($columns) VALUES ($values)";

        return $this;
    }

    /**
     * Update statement.
     *
     * @return $this
     */
    public function update(string $table, array $columns): Query
    {
        $this->bindings = array_merge($this->bindings, array_values($columns));

        $columns = Helpers::implode(
            array_keys($columns),
            sprintf('%s = ?', Helpers::BACKTICKED_MASK),
        );

        $this->query[] = "UPDATE `$table` SET $columns";

        return $this;
    }

    /**
     * Delete statement.
     *
     * @return $this
     */
    public function delete(string $table): Query
    {
        $this->query[] = "DELETE FROM `$table`";

        return $this;
    }

    /**
     * Where statement.
     *
     * @return $this
     */
    public function where(array $columns): Query
    {
        foreach ($columns as $column => $value) {
            if (is_scalar($value)) {
                $operator = WhereOperator::Equal;
            } else {
                $operator = WhereOperator::from($value[0]);
                $value = $value[1];
            }

            $this->whereQueries[] = new WhereQuery(
                boolean: WhereBoolean::And,
                operator: $operator,
                column: $column,
                value: $value,
            );
        }

        return $this;
    }

    /**
     * Where In statement.
     *
     * @param array|string $column
     *
     * @return $this
     */
    public function whereIn(
        $column,
        array $values,
        WhereOperator $operator = WhereOperator::In
    ): Query {
        if (is_string($column)) {
            $this->whereQueries[] = new WhereInQuery(
                boolean: WhereBoolean::And,
                operator: $operator,
                column: $column,
                multipleValues: $values,
            );
        } else {
            $this->whereQueries[] = new WhereInCompositeQuery(
                boolean: WhereBoolean::And,
                operator: $operator,
                multipleColumns: $column, // :|
                multipleValues: $values,
            );
        }

        return $this;
    }

    /**
     * Where Not In statement.
     *
     * @param array|string $column
     *
     * @return $this
     */
    public function whereNotIn($column, array $values): Query
    {
        return $this->whereIn($column, $values, WhereOperator::NotIn);
    }

    /**
     * Compile all where statements.
     */
    protected function compileWheres(): void
    {
        if ([] === $this->whereQueries) {
            return;
        }

        $this->query[] = 'WHERE';

        foreach ($this->whereQueries as $index => $whereQuery) {
            $result = $whereQuery->compile($index);

            $this->query[] = $result->output;
            $this->bindings = \array_merge(
                $this->bindings,
                $result->bindings,
            );
        }
    }
}
