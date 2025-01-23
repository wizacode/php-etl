<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Unit;

use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Tools\AbstractTestCase;
use Wizaplace\Etl\Extractors\Extractor;
use Wizaplace\Etl\Loaders\Loader;
use Wizaplace\Etl\Pipeline;
use Wizaplace\Etl\Row;
use Wizaplace\Etl\Transformers\Transformer;

class PipelineTest extends AbstractTestCase
{
    /** @var MockObject|Row */
    private $row1;

    /** @var MockObject|Row */
    private $row2;

    /** @var MockObject|Row */
    private $row3;

    /** @var MockObject|Extractor */
    private $extractor;

    /** @var MockObject|Transformer */
    private $transformer;

    /** @var MockObject|Loader */
    private $loader;

    private Pipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->row1 = $this->createMock('Wizaplace\Etl\Row');
        $this->row1->expects(static::any())->method('toArray')->willReturn(['row1']);

        $this->row2 = $this->createMock('Wizaplace\Etl\Row');
        $this->row2->expects(static::any())->method('toArray')->willReturn(['row2']);

        $this->row3 = $this->createMock('Wizaplace\Etl\Row');
        $this->row3->expects(static::any())->method('toArray')->willReturn(['row3']);

        $generator = function (): Generator {
            yield $this->row1;
            yield $this->row2;
            yield $this->row3;
        };

        $this->extractor = $this->createMock('Wizaplace\Etl\Extractors\Extractor');
        $this->extractor->expects(static::any())->method('extract')->willReturn($generator());

        $this->transformer = $this->createMock('Wizaplace\Etl\Transformers\Transformer');

        $this->transformer->expects(static::any())->method('transform')
            ->with($this->row1);
        $this->transformer->expects(static::any())->method('transform')
            ->with($this->row2);
        $this->transformer->expects(static::any())->method('transform')
            ->with($this->row3);

        $this->loader = $this->createMock('Wizaplace\Etl\Loaders\Loader');

        $this->loader->expects(static::any())->method('load')
            ->with($this->row1);
        $this->loader->expects(static::any())->method('load')
            ->with($this->row2);
        $this->loader->expects(static::any())->method('load')
            ->with($this->row3);

        $this->pipeline = new Pipeline();
        $this->pipeline->extractor($this->extractor);
    }

    /** @test */
    public function pipelineFlow(): void
    {
        $this->row1->expects(static::once())->method('toArray');
        $this->row2->expects(static::once())->method('toArray');
        $this->row3->expects(static::once())->method('toArray');

        $this->extractor->expects(static::once())->method('extract');
        $this->extractor->expects(static::once())->method('initialize');
        $this->extractor->expects(static::once())->method('finalize');

        $this->transformer->expects(static::exactly(3))->method('transform');
        $this->transformer->expects(static::once())->method('initialize');
        $this->transformer->expects(static::once())->method('finalize');

        $this->loader->expects(static::exactly(3))->method('load');
        $this->loader->expects(static::once())->method('initialize');
        $this->loader->expects(static::once())->method('finalize');

        $this->pipeline->pipe($this->transformer);
        $this->pipeline->pipe($this->loader);

        static::assertEquals(
            [['row1'], ['row2'], ['row3']],
            $this->pipelineToArray($this->pipeline)
        );
    }

    /** @test */
    public function limitNumberOfRows(): void
    {
        $this->pipeline->limit(1);

        static::assertEquals([['row1']], $this->pipelineToArray($this->pipeline));
    }

    /** @test */
    public function skipInitialRows(): void
    {
        $this->pipeline->skip(2);

        static::assertEquals([['row3']], $this->pipelineToArray($this->pipeline));

        $this->pipeline->skip(3);

        static::assertEquals([], $this->pipelineToArray($this->pipeline));
    }

    /** @test */
    public function limitAfterSkippingRows(): void
    {
        $this->pipeline->skip(1);
        $this->pipeline->limit(1);

        static::assertEquals([['row2']], $this->pipelineToArray($this->pipeline));
    }

    /** @test */
    public function discardRows(): void
    {
        $this->row2->expects(static::once())->method('discarded')->willReturn(true);

        $this->pipeline->pipe($this->transformer);
        $this->pipeline->pipe($this->loader);

        $this->transformer->expects(static::exactly(2))->method('transform');
        $this->loader->expects(static::exactly(2))->method('load');

        // The data has marked as discarded, but it is still present in the pipeline, therefore we still expect to have
        // row2 in the output when the pipeline is converted to an array.
        static::assertEquals(
            [['row1'], ['row2'], ['row3']],
            $this->pipelineToArray($this->pipeline)
        );
    }

    /** @test */
    public function recursionWhenConsecutiveRowsDiscarded(): void
    {
        $allowedStackSize = 50;

        $transformer = $this->createMock('Wizaplace\Etl\Transformers\Transformer');
        $loader = $this->createMock('Wizaplace\Etl\Loaders\Loader');

        $numRows = $allowedStackSize * 2;
        $toNotDiscard = (int) floor($numRows / 10);
        $generator = function () use ($transformer, $loader, $allowedStackSize, $numRows, $toNotDiscard): Generator {
            $i = 0;
            while ($i < $numRows) {
                $actualStackSize = count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $allowedStackSize));
                self::assertLessThan($allowedStackSize, $actualStackSize);

                $row = $this->createMock('Wizaplace\Etl\Row');
                $row->expects(static::any())->method('toArray')->willReturn(["row$i"]);
                $i++;
                if ($i > $toNotDiscard) {
                    $row->expects(static::any())->method('discarded')->willReturn(true);
                }
                $transformer->expects(static::any())->method('transform')->with($row);
                $loader->expects(static::any())->method('load')->with($row);

                yield $row;
            }
        };

        $extractor = $this->createMock('Wizaplace\Etl\Extractors\Extractor');
        $extractor->expects(static::any())->method('extract')->willReturn($generator());

        $pipeline = new Pipeline();
        $pipeline->extractor($extractor);

        $pipeline->pipe($transformer);
        $pipeline->pipe($loader);

        $transformer->expects(static::exactly($toNotDiscard))->method('transform');
        $loader->expects(static::exactly($toNotDiscard))->method('load');

        $this->pipelineToArray($pipeline);
    }

    protected function pipelineToArray(Pipeline $pipeline): array
    {
        return array_map(
            function (Row $row): array {
                return $row->toArray();
            },
            iterator_to_array($pipeline)
        );
    }
}
