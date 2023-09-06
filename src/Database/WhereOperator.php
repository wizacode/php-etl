<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

enum WhereOperator: string
{
    case Equal = '=';
    case Less = '<';
    case LessOrEqual = '<=';
    case Greater = '>';
    case GreaterOrEqual = '>=';
    case NotEqual = '<>';
    case NotEqualBis = '!=';
    case In = 'IN';
    case NotIn = 'NOT IN';
}
