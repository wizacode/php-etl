<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Loaders;

class Update extends InsertUpdate
{
    public const CONNECTION = 'connection';
    public const KEY = 'key';
    public const TIMESTAMPS = 'timestamps';
    public const TRANSACTION = 'transaction';

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
    ];

    /**
     * Execute the given row.
     */
    protected function execute(array $row): void
    {
        [$row, $current] = $this->prepareEntry($row);

        if (false !== $current) {
            $this->update($row, $current);
        }
    }
}
