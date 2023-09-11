<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

class WhereInCompositeQuery implements WhereInterface
{
    public function __construct(
        private WhereBoolean $boolean,
        private WhereOperator $operator,
        private array $multipleColumns,
        private array $multipleValues,
    ) {
    }

    public function compile(int $index): WhereCompileResult
    {
        sort($this->multipleColumns);

        $parameters = [];
        $bindings = [];
        foreach ($this->multipleValues as $value) {
            ksort($value);

            $bindings = array_merge($bindings, array_values($value));

            $parameters[] = \sprintf(
                '(%s)',
                Helpers::implode($value, '?')
            );
        }

        $parameters = Helpers::implode($parameters);

        $multipleColumns = Helpers::implode($this->multipleColumns, Helpers::BACKTICKED_MASK);

        return new WhereCompileResult(
            \trim(
                \sprintf(
                    '%s (%s) %s (%s)',
                    $index > 0 ? $this->boolean->value : '',
                    $multipleColumns,
                    $this->operator->value,
                    $parameters,
                )
            ),
            $bindings
        );
    }
}
