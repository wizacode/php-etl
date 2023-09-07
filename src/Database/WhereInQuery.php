<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

class WhereInQuery implements WhereInterface
{
    public function __construct(
        private WhereBoolean $boolean,
        private WhereOperator $operator,
        private string $column,
        private array $multipleValues,
    ) {
    }

    public function compile(int $index): WhereCompileResult
    {
        $parameters = Helpers::implode($this->multipleValues, '?');

        return new WhereCompileResult(
            \trim(
                \sprintf(
                    '%s `%s` %s (%s)',
                    $index > 0 ? $this->boolean->value : '',
                    $this->column,
                    $this->operator->value,
                    $parameters,
                )
            ),
            $this->multipleValues,
        );
    }
}
