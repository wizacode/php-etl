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
use Wizaplace\Etl\Transformers\JsonDecode;

class JsonDecodeTest extends AbstractTestCase
{
    /**
     * Row array to be transformed in testing.
     *
     * @var Row[]
     */
    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->data = [
            new Row(['id' => '"1"', 'data' => '{"name":"John Doe","email":"johndoe@email.com"}']),
            new Row(['id' => '"2"', 'data' => '{"name":"Jane Doe","email":"janedoe@email.com"}']),
        ];
    }

    public function testDefaultOptions(): void
    {
        $expected = [
            new Row(['id' => '1', 'data' => (object) ['name' => 'John Doe', 'email' => 'johndoe@email.com']]),
            new Row(['id' => '2', 'data' => (object) ['name' => 'Jane Doe', 'email' => 'janedoe@email.com']]),
        ];

        $transformer = new JsonDecode();

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }

    public function testConvertingObjectsToAssociativeArrays(): void
    {
        $expected = [
            new Row(['id' => '1', 'data' => ['name' => 'John Doe', 'email' => 'johndoe@email.com']]),
            new Row(['id' => '2', 'data' => ['name' => 'Jane Doe', 'email' => 'janedoe@email.com']]),
        ];

        $transformer = new JsonDecode();

        $transformer->options([$transformer::ASSOC => true]);

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }

    public function testCustomColumns(): void
    {
        $expected = [
            new Row(['id' => '"1"', 'data' => (object) ['name' => 'John Doe', 'email' => 'johndoe@email.com']]),
            new Row(['id' => '"2"', 'data' => (object) ['name' => 'Jane Doe', 'email' => 'janedoe@email.com']]),
        ];

        $transformer = new JsonDecode();

        $transformer->options([$transformer::COLUMNS => ['data']]);

        $this->execute($transformer, $this->data);

        static::assertEquals($expected, $this->data);
    }
}
