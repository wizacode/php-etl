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
use Wizaplace\Etl\Transformers\Trim;

class TrimTest extends AbstractTestCase
{
    /**
     * Row array to be transformed in testing.
     *
     * @var Row[]
     */
    protected array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            new Row(['id' => ' 1', 'name' => 'John Doe  ', 'email' => ' johndoe@email.com ']),
            new Row(['id' => '2 ', 'name' => '  Jane Doe', 'email' => '  janedoe@email.com  ']),
        ];
    }

    public function testDefaultOptions(): void
    {
        $expected = [
            new Row(['id' => '1', 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => '2', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $transformer = new Trim();

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }

    public function testCustomColumns(): void
    {
        $expected = [
            new Row(['id' => '1', 'name' => 'John Doe', 'email' => ' johndoe@email.com ']),
            new Row(['id' => '2', 'name' => 'Jane Doe', 'email' => '  janedoe@email.com  ']),
        ];

        $transformer = new Trim();

        $transformer->options([$transformer::COLUMNS => ['id', 'name']]);

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }

    public function testTrimRight(): void
    {
        $expected = [
            new Row(['id' => ' 1', 'name' => 'John Doe', 'email' => ' johndoe@email.com']),
            new Row(['id' => '2', 'name' => '  Jane Doe', 'email' => '  janedoe@email.com']),
        ];

        $transformer = new Trim();

        $transformer->options([$transformer::TYPE => 'right']);

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }

    public function testTrimLeft(): void
    {
        $expected = [
            new Row(['id' => '1', 'name' => 'John Doe  ', 'email' => 'johndoe@email.com ']),
            new Row(['id' => '2 ', 'name' => 'Jane Doe', 'email' => 'janedoe@email.com  ']),
        ];

        $transformer = new Trim();

        $transformer->options([$transformer::TYPE => 'left']);

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }

    public function testCustomCharacterMask(): void
    {
        $expected = [
            new Row(['id' => '1', 'name' => 'John Doe', 'email' => 'johndoe@email']),
            new Row(['id' => '2', 'name' => 'Jane Doe', 'email' => 'janedoe@email']),
        ];

        $transformer = new Trim();

        $transformer->options([$transformer::MASK => ' cmo.']);

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }

    public function testThrowsExceptionForUnsupportedTrimType(): void
    {
        $transformer = new Trim();

        $transformer->options([$transformer::TYPE => 'invalid']);

        $this->expectException('InvalidArgumentException');

        $this->execute($transformer, $this->data);
    }
}
