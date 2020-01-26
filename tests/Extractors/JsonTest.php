<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     MIT
 */

namespace Tests\Extractors;

use Tests\TestCase;
use Wizaplace\Etl\Row;
use Wizaplace\Etl\Extractors\Json;

class JsonTest extends TestCase
{
    /** @test */
    public function default_options()
    {
        $expected = [
            new Row(['id' => 1, 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => 2, 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $extractor = new Json;

        $extractor->input(__DIR__.'/../data/json1.json');

        $this->assertEquals($expected, iterator_to_array($extractor->extract()));
    }

    /** @test */
    public function custom_columns_json_path()
    {
        $expected = [
            new Row(['id' => 1, 'name' => 'John Doe', 'email' => 'johndoe@email.com']),
            new Row(['id' => 2, 'name' => 'Jane Doe', 'email' => 'janedoe@email.com']),
        ];

        $extractor = new Json;

        $extractor->input(__DIR__.'/../data/json2.json');
        $extractor->options(['columns' => [
            'id' => '$..bindings[*].id.value',
            'name' => '$..bindings[*].name.value',
            'email' => '$..bindings[*].email.value',
        ]]);

        $this->assertEquals($expected, iterator_to_array($extractor->extract()));
    }
}
