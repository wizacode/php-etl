<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit\Transformers;

use Tests\Tools\AbstractTestCase;
use Wizaplace\Etl\Row;
use Wizaplace\Etl\Transformers\RenameColumns;

class RenameColumnsTest extends AbstractTestCase
{
    public function testRenameColumn(): void
    {
        $data = [
            new Row(['id' => '1', 'name' => 'John Doe', 'email_address' => 'johndoe@email.com']),
            new Row(['id' => '2', 'name' => 'Jane Doe', 'email_address' => 'janedoe@email.com']),
        ];

        $expected = [
            new Row(['id' => '1', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => '2', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $transformer = new RenameColumns();

        $transformer->options([$transformer::COLUMNS => ['email_address' => 'email']]);

        $this->execute($transformer, $data);

        static::assertEquals($expected, $data);
    }
}
