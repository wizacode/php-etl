<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     MIT
 */

namespace Tests;

use Wizaplace\Etl\Step;

class StepTest extends TestCase
{
    /** @test */
    public function set_options()
    {
        $step = new FakeStep;

        $step->options([
            'option1' => 'value',
            'option2' => 'value',
        ]);

        $this->assertEquals('value', $step->getOption('Option1'));
        $this->assertNull($step->getOption('Option2'));
    }
}

class FakeStep extends Step
{
    protected $option1;
    protected $option2;
    protected $availableOptions = ['option1'];

    public function getOption(string $name)
    {
        $name = lcfirst($name);

        return $this->$name ?? null;
    }
}
