<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Loaders;

use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Database\Transaction;

abstract class AbstractDbLoader extends Loader
{
    /**
     * The database manager.
     */
    protected Manager $db;

    /**
     * Time for timestamps columns.
     */
    protected string $time;

    /**
     * Indicates if the table has timestamps columns.
     */
    protected bool $timestamps = false;

    /**
     * Indicates if the loader will perform transactions.
     */
    protected bool $transaction = true;

    /**
     * The database transaction manager.
     */
    protected Transaction $transactionManager;

    /**
     * The connection name.
     */
    protected string $connection = 'default';

    /**
     * Transaction commit size.
     */
    protected int $commitSize = 0;

    /**
     * The columns to insert.
     *
     * @var string[]
     */
    protected array $columns = [];

    /**
     * Create a new Insert Loader instance.
     */
    public function __construct(Manager $manager)
    {
        $this->db = $manager;
    }

    public function initialize(): void
    {
        if ($this->timestamps) {
            $this->time = date('Y-m-d G:i:s');
        }

        if ($this->transaction) {
            $this->transactionManager = $this->db->transaction($this->connection)->size($this->commitSize);
        }

        if ([] !== $this->columns && array_keys($this->columns) === range(0, count($this->columns) - 1)) {
            $this->columns = array_combine($this->columns, $this->columns);
        }
    }

    /**
     * Finalize the step.
     */
    public function finalize(): void
    {
        if ($this->transaction) {
            $this->transactionManager->close();
        }
    }
}
