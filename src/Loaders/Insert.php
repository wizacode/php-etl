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

class Insert extends AbstractDbLoader
{
    public const CONNECTION = 'connection';
    public const TIMESTAMPS = 'timestamps';
    public const TRANSACTION = 'transaction';
    public const COMMIT_SIZE = 'commitSize';

    /**
     * The insert statement.
     */
    protected \PDOStatement $insert;

    /**
     * Properties that can be set via the options method.
     *
     * @var string[]
     */
    protected array $availableOptions = [
        self::COLUMNS,
        self::CONNECTION,
        self::TIMESTAMPS,
        self::TRANSACTION,
        self::COMMIT_SIZE,
    ];

    /**
     * Load the given row.
     */
    public function load(Row $row): void
    {
        $row = $row->toArray();

        if ($this->transaction) {
            $this->transactionManager->run(function () use ($row): void {
                $this->insert($row);
            });
        } else {
            $this->insert($row);
        }
    }

    /**
     * Prepare the insert statement.
     */
    protected function prepareInsert(array $sample): void
    {
        if ([] !== $this->columns) {
            $columns = array_values($this->columns);
        } else {
            $columns = array_keys($sample);
        }

        if ($this->timestamps) {
            array_push($columns, 'created_at', 'updated_at');
        }

        $this->insert = $this->db->statement($this->connection)->insert($this->output, $columns)->prepare();
    }

    /**
     * Execute the insert query.
     */
    protected function insert(array $row): void
    {
        if (!isset($this->insert)) {
            $this->prepareInsert($row);
        }

        if ([] !== $this->columns) {
            $result = [];

            foreach ($this->columns as $key => $column) {
                isset($row[$key]) ? $result[$column] = $row[$key] : $result[$column] = null;
            }

            $row = $result;
        }

        if ($this->timestamps) {
            $row['created_at'] = $this->time;
            $row['updated_at'] = $this->time;
        }

        $this->insert->execute($row);
    }
}
