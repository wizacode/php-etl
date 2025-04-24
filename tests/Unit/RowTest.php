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
use Wizaplace\Etl\Row;

class RowTest extends AbstractTestCase
{
    public function testSetAttribute(): void
    {
        $row = new Row([]);

        $row->set('name', 'Jane Doe');

        static::assertEquals('Jane Doe', $row->get('name'));
    }

    public function testGetAttribute(): void
    {
        $row = new Row(['name' => 'Jane Doe']);

        static::assertEquals('Jane Doe', $row->get('name'));
        static::assertNull($row->get('invalid'));
    }

    public function testRemoveAttribute(): void
    {
        $row = new Row(['name' => 'Jane Doe']);

        $row->remove('name');

        static::assertNull($row->get('name'));
    }

    public function testPullAttribute(): void
    {
        $row = new Row(['name' => 'Jane Doe']);

        $value = $row->pull('name');

        static::assertEquals('Jane Doe', $value);
        static::assertNull($row->get('name'));
    }

    public function testTransformValuesUsingCallback(): void
    {
        $row = new Row(['name' => 'Jane Doe', 'email' => 'janedoe@example.com']);

        $row->transform([], function (string $value): string {
            return "*$value*";
        });

        static::assertEquals('*Jane Doe*', $row->get('name'));
        static::assertEquals('*janedoe@example.com*', $row->get('email'));

        $row->transform(['name'], function (string $value): string {
            return trim($value, '*');
        });

        static::assertEquals('Jane Doe', $row->get('name'));
        static::assertEquals('*janedoe@example.com*', $row->get('email'));
    }

    public function testGetArrayRepresentationOfRow(): void
    {
        $row = new Row(['name' => 'Jane Doe']);

        static::assertEquals(['name' => 'Jane Doe'], $row->toArray());
    }

    public function testDiscardRow(): void
    {
        $row = new Row([]);

        static::assertFalse($row->discarded());

        $row->discard();

        static::assertTrue($row->discarded());
    }

    public function testArrayAccess(): void
    {
        $row = new Row(['name' => 'Jane Doe']);

        static::assertTrue(isset($row['name']));

        static::assertEquals('Jane Doe', $row['name']);

        $row['name'] = 'John Doe';

        static::assertEquals('John Doe', $row['name']);

        unset($row['name']);

        static::assertFalse(isset($row['name']));
    }

    public function testObjectAccess(): void
    {
        $row = new Row(['name' => 'Jane Doe']);

        static::assertEquals('Jane Doe', $row->name);

        $row->name = 'John Doe';

        static::assertEquals('John Doe', $row->name);
    }

    public function testSetAttributesWithoutMerge(): void
    {
        $row = new Row(['name' => 'Jane Doe', 'Sex' => 'Female']);
        $newAttributes = ['name' => 'Pocahontas', 'Sex' => 'Female'];
        $row->setAttributes($newAttributes);
        static::assertEquals($newAttributes, $row->toArray());
    }

    public function testSetAttributesWithMerge(): void
    {
        $row = new Row(['name' => 'Jane Doe', 'Sex' => 'Female']);
        $newAttributes = ['name' => 'Marie Curie', 'Job' => 'Scientist'];
        $row->setAttributes($newAttributes, true);
        static::assertEquals([
            'name' => 'Marie Curie',
            'Sex' => 'Female',
            'Job' => 'Scientist',
        ], $row->toArray());
    }

    public function testClearAttributes(): void
    {
        $row = new Row(['name' => 'Jane Doe', 'Sex' => 'Female']);
        $row->clearAttributes();
        static::assertEmpty($row->toArray());
    }
}
