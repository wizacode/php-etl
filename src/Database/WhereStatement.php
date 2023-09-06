<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

class WhereStatement
{
    const TYPE = 'type';
    const BOOLEAN = 'boolean';
    const MULTIPLE_COLUMNS = 'columns';
    const COLUMN = 'column';
    const OPERATOR = 'operator';
    const MULTIPLE_VALUES = 'values';
    const VALUE = 'value';
}
