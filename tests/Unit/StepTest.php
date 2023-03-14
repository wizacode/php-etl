<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Tools\AbstractTestCase;
use Tests\Tools\FakeStep;

class StepTest extends AbstractTestCase
{
    /** @test */
    public function setOptionsWithStrictModeDisabled(): void
    {
        $step = new FakeStep();

        $step->options([
            'option1' => 'value1',
            'option2' => 'value2',
        ]);

        static::assertEquals('value1', $step->getOption('Option1'));
        static::assertNull($step->getOption('Option2'));
    }

    /** @test */
    public function setOptionsWithStrictModeEnabled(): void
    {
        $step = new FakeStep();

        static::expectExceptionMessage("Unknown option: 'option2' with value 'value2'");

        $step->options([
            'option1' => 'value1',
            'option2' => 'value2',
        ], true);
    }
}
