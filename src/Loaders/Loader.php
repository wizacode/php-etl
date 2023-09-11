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
use Wizaplace\Etl\Step;

abstract class Loader extends Step
{
    /**
     * The loader output.
     */
    protected mixed $output;

    /**
     * Set the loader output.
     *
     * @return $this
     */
    public function output(mixed $output): Loader
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Load the given row.
     */
    abstract public function load(Row $row): void;
}
