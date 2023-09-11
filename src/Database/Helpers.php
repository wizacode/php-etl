<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

class Helpers
{
    public const DEFAULT_MASK = '{column}';
    public const BACKTICKED_MASK = '`{column}`';

    /**
     * Join array elements using a string mask.
     */
    public static function implode(
        array $columns,
        string $mask = self::DEFAULT_MASK,
        array $ignoreMask = ['*'] // No backticks for *
    ): string {
        $columns = array_map(
            function ($column) use ($mask, $ignoreMask): string {
                return \in_array($column, $ignoreMask, true)
                    ? $column
                    : str_replace(self::DEFAULT_MASK, $column, $mask);
            },
            $columns
        );

        return implode(', ', $columns);
    }
}
