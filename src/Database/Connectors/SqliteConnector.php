<?php

declare(strict_types=1);

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     MIT
 */

namespace Wizaplace\Etl\Database\Connectors;

class SqliteConnector extends Connector
{
    /**
     * Connect to a database.
     *
     * @return \PDO
     */
    public function connect(array $config)
    {
        return $this->createConnection('sqlite:' . $config['database'], $config);
    }
}
