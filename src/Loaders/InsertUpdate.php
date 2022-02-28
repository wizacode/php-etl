<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Loaders;

use Wizaplace\Etl\Row;

class InsertUpdate extends Insert
{
    public const CONNECTION = 'connection';
    public const KEY = 'key';
    public const TIMESTAMPS = 'timestamps';
    public const TRANSACTION = 'transaction';
    public const DO_UPDATES = 'doUpdates';

    /**
     * The primary key.
     *
     * @var mixed
     */
    protected $key = ['id'];

    /**
     * Indicates if existing destination rows in table should be updated.
     */
    protected bool $doUpdates = true;

    /**
     * The select statement.
     *
     * @var \PDOStatement|false|null
     */
    protected $select = null;

    /**
     * The update statement.
     *
     * @var \PDOStatement|false|null
     */
    protected $update = null;

    /**
     * Properties that can be set via the options method.
     *
     * @var string[]
     */
    protected array $availableOptions = [
        self::COLUMNS,
        self::CONNECTION,
        self::KEY,
        self::TIMESTAMPS,
        self::TRANSACTION,
        self::DO_UPDATES,
    ];

    /**
     * Load the given row.
     */
    public function load(Row $row): void
    {
        $row = $row->toArray();

        if ($this->transaction) {
            $this->transactionManager->run(function () use ($row): void {
                $this->execute($row);
            });
        } else {
            $this->execute($row);
        }
    }

    /**
     * Prepare the select statement.
     */
    protected function prepareSelect(): void
    {
        $this->select = $this->db->statement($this->connection)->select($this->output)->where($this->key)->prepare();
    }

    /**
     * Prepare the update statement.
     *
     * @param string[] $sample
     */
    protected function prepareUpdate(array $sample): void
    {
        if ([] !== $this->columns) {
            $columns = array_values(array_diff($this->columns, $this->key));
        } else {
            $columns = array_keys(array_diff_key($sample, array_flip($this->key)));
        }

        if ($this->timestamps) {
            array_push($columns, 'updated_at');
        }

        $this->update = $this->db->statement($this->connection)
            ->update($this->output, $columns)
            ->where($this->key)
            ->prepare();
    }

    /**
     * Execute the given row.
     */
    protected function execute(array $row): void
    {
        [$row, $current] = $this->prepareEntry($row);

        if (false === $current) {
            $this->insert($row);
        } else {
            $this->update($row, $current);
        }
    }

    protected function prepareEntry(array $row): array
    {
        if (null === $this->select) {
            $this->prepareSelect();
        }

        if ([] !== $this->columns) {
            $mappedColumnsArr = [];
            $keyColumns = array_intersect($this->columns, $this->key);

            foreach ($keyColumns as $key => $column) {
                $mappedColumnsArr[$column] = array_intersect_key($row, $keyColumns)[$key];
            }
            $this->select->execute($mappedColumnsArr);
        } else {
            $this->select->execute(array_intersect_key($row, array_flip($this->key)));
        }

        if ([] !== $this->columns) {
            $result = [];

            foreach ($this->columns as $key => $column) {
                isset($row[$key]) ? $result[$column] = $row[$key] : $result[$column] = null;
            }

            $row = $result;
        }

        $current = $this->select->fetch();

        return [$row, $current];
    }

    /**
     * Execute the insert statement.
     */
    protected function insert(array $row): void
    {
        if (!isset($this->insert)) {
            $this->prepareInsert($row);
        }

        if ($this->timestamps) {
            $row['created_at'] = $this->time;
            $row['updated_at'] = $this->time;
        }

        $this->insert->execute($row);
    }

    /**
     * Execute the update statement.
     */
    protected function update(array $row, array $current): void
    {
        if (false === $this->doUpdates) {
            return;
        }

        if (null === $this->update) {
            $this->prepareUpdate($row);
        }

        if ($row === array_intersect_key($current, $row)) {
            return;
        }

        if ($this->timestamps) {
            $row['updated_at'] = $this->time;
        }

        $this->update->execute($row);
    }
}
