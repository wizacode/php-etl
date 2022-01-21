<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl;

abstract class Step
{
    public const COLUMNS = 'columns';
    public const INDEX = 'index';

    /**
     * Properties that can be set via the options method.
     *
     * @var string[]
     */
    protected array $availableOptions = [];

    /**
     * Set the step options.
     *
     * @param bool $strictMode if set to true, will throw an exception if an unknown option is given
     *
     * @return $this
     */
    public function options(array $options, bool $strictMode = false): Step
    {
        foreach ($options as $option => $value) {
            $option = lcfirst(implode('', array_map('ucfirst', explode('_', $option))));

            if (in_array($option, $this->availableOptions, true)) {
                $this->$option = $value;
            } elseif (true === $strictMode) {
                throw new \InvalidArgumentException("Unknown option: '$option' with value '$value'");
            }
        }

        return $this;
    }

    /**
     * Initialize the step.
     */
    public function initialize(): void
    {
    }

    /**
     * Finalize the step.
     */
    public function finalize(): void
    {
    }
}
