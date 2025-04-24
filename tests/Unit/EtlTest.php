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
use Wizaplace\Etl\Etl;
use Wizaplace\Etl\Row;

class EtlTest extends AbstractTestCase
{
    public function testExtractStep(): void
    {
        $extractor = $this->createMock('Wizaplace\Etl\Extractors\Extractor');
        $extractor->expects(static::once())->method('input')->with('input')->willReturnSelf();
        $extractor->expects(static::once())->method('options')->with(['options']);

        $pipeline = $this->createMock('Wizaplace\Etl\Pipeline');
        $pipeline->expects(static::once())->method('extractor')->with($extractor);

        $etl = new Etl($pipeline);

        static::assertInstanceOf(Etl::class, $etl->extract($extractor, 'input', ['options']));
    }

    public function testTransformStep(): void
    {
        $transformer = $this->createMock('Wizaplace\Etl\Transformers\Transformer');
        $transformer->expects(static::once())->method('options')->with(['options']);

        $pipeline = $this->createMock('Wizaplace\Etl\Pipeline');
        $pipeline->expects(static::once())->method('pipe')->with($transformer);

        $etl = new Etl($pipeline);

        static::assertInstanceOf(Etl::class, $etl->transform($transformer, ['options']));
    }

    public function testLoadStep(): void
    {
        $loader = $this->createMock('Wizaplace\Etl\Loaders\Loader');
        $loader->expects(static::once())->method('output')->with('output')->willReturnSelf();
        $loader->expects(static::once())->method('options')->with(['options']);

        $pipeline = $this->createMock('Wizaplace\Etl\Pipeline');
        $pipeline->expects(static::once())->method('pipe')->with($loader);

        $etl = new Etl($pipeline);

        static::assertInstanceOf(Etl::class, $etl->load($loader, 'output', ['options']));
    }

    public function testRunTheEtl(): void
    {
        $pipeline = $this->createMock('Wizaplace\Etl\Pipeline');
        $pipeline->expects(static::exactly(1))->method('rewind');
        $pipeline->expects(static::exactly(3))->method('valid')->willReturnOnConsecutiveCalls(true, true, false);
        $pipeline->expects(static::exactly(2))->method('next');

        $etl = new Etl($pipeline);

        $etl->run();
    }

    public function testGetArrayOfEtlData(): void
    {
        $pipeline = $this->createMock('Wizaplace\Etl\Pipeline');
        $pipeline->expects(static::exactly(4))->method('valid')->willReturnOnConsecutiveCalls(true, true, true, false);
        $pipeline->expects(static::exactly(3))->method('current')->willReturnOnConsecutiveCalls(
            new Row(['row1']),
            (new Row(['row2']))->discard(),
            new Row(['row3'])
        );
        $pipeline->expects(static::exactly(3))->method('next');

        $etl = new Etl($pipeline);

        static::assertEquals([['row1'], ['row3']], $etl->toArray());
    }
}
