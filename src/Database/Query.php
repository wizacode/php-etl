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
    private const DEFAULT_MASK = '{column}';
    public const BACKTICKED_MASK = '`{column}`';

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
     * @var WhereStatementInterface[]
     */
    protected array $whereStatements = [];

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

    /**

     * @return $this
     */
    public function select(string $table, ?array $columns = null): Query
    {

        $columns = \is_null($columns)
            ? '*'
            : self::implode($columns, self::BACKTICKED_MASK);

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

        $values = self::implode($columns, '?');

        $columns = self::implode(array_keys($columns), self::BACKTICKED_MASK);

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

        $columns = self::implode(
            array_keys($columns),
            sprintf("%s = ?", self::BACKTICKED_MASK),
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

            $this->whereStatements[] = new WhereStatement(
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
            $this->whereStatements[] = new WhereInStatement(
                boolean: WhereBoolean::And,
                operator: $operator,
                column: $column,
                multipleValues: $values,
            );
        } else {
            $this->whereStatements[] = new WhereInCompositeStatement(
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
        if ([] === $this->whereStatements) {
            return;
        }

        $this->query[] = 'WHERE';

        foreach ($this->whereStatements as $index => $statement) {
            $result = $statement->compile($index);

            $this->query[] = $result->statement;
            $this->bindings = \array_merge(
                $this->bindings,
                $result->bindings,
            );
        }
    }

    /**
     * Join array elements using a string mask.
     */
    public static function implode(array $columns, string $mask = self::DEFAULT_MASK): string
    {
        $columns = array_map(function ($column) use ($mask): string {
            return str_replace(self::DEFAULT_MASK, $column, $mask);
        }, $columns);

        return implode(', ', $columns);
    }
}
