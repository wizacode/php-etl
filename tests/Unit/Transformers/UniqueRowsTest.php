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
use Wizaplace\Etl\Transformers\UniqueRows;

class UniqueRowsTest extends AbstractTestCase
{
    public function testCompareAllColumns(): void
    {
        $data = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $expected = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']))->discard(),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']))->discard(),
        ];

        $transformer = new UniqueRows();

        $this->execute($transformer, $data);

        static::assertEquals($expected, $data);
    }

    public function testCompareTheGivenColumns(): void
    {
        $data = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.org']),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.net']),
        ];

        $expected = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.org']))->discard(),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.net']))->discard(),
        ];

        $transformer = new UniqueRows();

        $transformer->options([$transformer::COLUMNS => ['id', 'name']]);

        $this->execute($transformer, $data);

        static::assertEquals($expected, $data);
    }

    public function testCompareAllColumnsOfConsecutiveRows(): void
    {
        $data = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $expected = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']))->discard(),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']))->discard(),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            (new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']))->discard(),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $transformer = new UniqueRows();

        $transformer->options([$transformer::CONSECUTIVE => true]);

        $this->execute($transformer, $data);

        static::assertEquals($expected, $data);
    }

    public function testCompareTheGivenColumnsOfConsecutiveRows(): void
    {
        $data = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.net']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.org']),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.net']),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $expected = [
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.net']))->discard(),
            (new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.org']))->discard(),
            new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            (new Row(['id' => '2', 'name' => 'John Doe', 'email' => 'johndoe@email.net']))->discard(),
            new Row(['id' => '1', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $transformer = new UniqueRows();

        $transformer->options(
            [
                $transformer::CONSECUTIVE => true,
                $transformer::COLUMNS => ['id', 'name'],
            ]
        );

        $this->execute($transformer, $data);

        static::assertEquals($expected, $data);
    }
}
