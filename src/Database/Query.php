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
     */
    protected array $wheres = [];

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
    public function select(string $table, ?array $columns = null): Query
    {

        $columns = \is_null($columns)
            ? '*'
            : $this->implode($columns, '`{column}`');

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

        $values = $this->implode($columns, '?');

        $columns = $this->implode(array_keys($columns), '`{column}`');

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

        $columns = $this->implode(
            array_keys($columns),
            '`{column}` = ?'
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
            $condition = [
                WhereStatement::TYPE => WhereType::Where,
                WhereStatement::COLUMN => $column,
                WhereStatement::BOOLEAN => WhereBoolean::And,
            ];

            if (is_scalar($value)) {
                $condition[WhereStatement::OPERATOR] = WhereOperator::Equal;
                $condition[WhereStatement::VALUE] = $value;
            } else {
                $condition[WhereStatement::OPERATOR] = WhereOperator::from($value[0]);
                $condition[WhereStatement::VALUE] = $value[1];
            }

            $this->wheres[] = $condition;
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
            $this->wheres[] = [
                WhereStatement::TYPE => WhereType::WhereIn,
                WhereStatement::COLUMN => $column,
                WhereStatement::MULTIPLE_VALUES => $values,
                WhereStatement::OPERATOR => $operator,
                WhereStatement::BOOLEAN => WhereBoolean::And,
            ];
        } else {
            $this->wheres[] = [
                WhereStatement::TYPE => WhereType::CompositeWhereIn,
                WhereStatement::MULTIPLE_COLUMNS => $column,
                WhereStatement::MULTIPLE_VALUES  => $values,
                WhereStatement::OPERATOR => $operator,
                WhereStatement::BOOLEAN => WhereBoolean::And,
            ];
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
        if ([] === $this->wheres) {
            return;
        }

        $this->query[] = 'WHERE';

        foreach ($this->wheres as $index => $condition) {
            $method = 'compile' . $condition[WhereStatement::TYPE]->value;

            if (0 == $index) {
                $condition[WhereStatement::BOOLEAN] = WhereBoolean::Nothing;
            }

            $this->query[] = trim($this->{$method}($condition));
        }
    }

    /**
     * Compile the basic where statement.
     */
    protected function compileWhere(array $where): string
    {
        // All these if, empty, are here to clean the legacy code before the fork. See the git history.
        $boolean = array_key_exists(WhereStatement::BOOLEAN, $where) ? $where[WhereStatement::BOOLEAN]->value : null;
        $column = array_key_exists(WhereStatement::COLUMN, $where) ? $where[WhereStatement::COLUMN] : null;
        $operator = array_key_exists(WhereStatement::OPERATOR, $where) ? $where[WhereStatement::OPERATOR]->value : null;
        $value = array_key_exists(WhereStatement::VALUE, $where) ? $where[WhereStatement::VALUE] : null;

        $this->bindings[] = $value;

        return "$boolean `$column` $operator ?";
    }

    /**
     * Compile the where in statement.
     */
    protected function compileWhereIn(array $where): string
    {
        // All these if, empty, are here to clean the legacy code before the fork. See the git history.
        $boolean = array_key_exists(WhereStatement::BOOLEAN, $where) ? $where[WhereStatement::BOOLEAN]->value : null;
        $column = array_key_exists(WhereStatement::COLUMN, $where) ? $where[WhereStatement::COLUMN] : null;
        $operator = array_key_exists(WhereStatement::OPERATOR, $where) ? $where[WhereStatement::OPERATOR]->value : null;
        $multipleValues = array_key_exists(WhereStatement::MULTIPLE_VALUES, $where) ? $where[WhereStatement::MULTIPLE_VALUES] : null;

        $this->bindings = array_merge($this->bindings, $multipleValues);

        $parameters = $this->implode($multipleValues, '?');

        return "$boolean `$column` $operator ($parameters)";
    }

    /**
     * Compile the composite where in statecolumnment.
     */
    protected function compileCompositeWhereIn(array $where): string
    {
        // All these if, empty, are here to clean the legacy code before the fork. See the git history.
        $boolean = array_key_exists(WhereStatement::BOOLEAN, $where) ? $where[WhereStatement::BOOLEAN]->value : null;
        $multipleColumns = array_key_exists(WhereStatement::MULTIPLE_COLUMNS, $where) ? $where[WhereStatement::MULTIPLE_COLUMNS] : null;
        $operator = array_key_exists(WhereStatement::OPERATOR, $where) ? $where[WhereStatement::OPERATOR]->value : null;
        $multipleValues = array_key_exists(WhereStatement::MULTIPLE_VALUES, $where) ? $where[WhereStatement::MULTIPLE_VALUES] : null;

        sort($multipleColumns);

        $parameters = [];

        foreach ($multipleValues as $value) {
            ksort($value);

            $this->bindings = array_merge($this->bindings, array_values($value));

            $parameters[] = "({$this->implode($value, '?')})";
        }

        $parameters = $this->implode($parameters);

        $multipleColumns = $this->implode($multipleColumns, '`{column}`');

        return "$boolean ($multipleColumns) $operator ($parameters)";
    }

    /**
     * Join array elements using a string mask.
     */
    protected function implode(array $columns, string $mask = '{column}'): string
    {
        $columns = array_map(function ($column) use ($mask): string {
            return str_replace('{column}', $column, $mask);
        }, $columns);

        return implode(', ', $columns);
    }
}
