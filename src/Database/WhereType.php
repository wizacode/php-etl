<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Database;

enum WhereType: string
{
    case Where = 'Where';
    case WhereIn = 'WhereIn';
    case CompositeWhereIn = 'CompositeWhereIn';
}
