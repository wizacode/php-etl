<?php

declare(strict_types=1);

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

namespace Tests\Traits;

use Tests\TestCase;
use Wizaplace\Etl\Traits\FilePathTrait;

class FilePathTraitTest extends TestCase
{
    /** @test */
    public function can_recursivly_create_a_dir_path()
    {
        $base = uniqid();
        $filePath = sys_get_temp_dir() . "/phpunit_$base/test/output";

        $myObject = new class {
            use FilePathTrait;

            public function myTest(string $filePath)
            {
                return $this->checkOrCreateDir($filePath);
            }
        };

        static::assertEquals(true, $myObject->myTest($filePath));
    }
}