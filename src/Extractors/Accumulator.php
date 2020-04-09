<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     MIT
 */

namespace Wizaplace\Etl\Extractors;

use Wizaplace\Etl\Exception\MissingDataException;
use Wizaplace\Etl\Row;

class Accumulator extends Extractor
{
    /**
     * The matching key tuplet between iterators
     *
     * @var string[]
     */
    protected $index;

    /**
     * Columns
     */
    /** @var string[] */
    protected $columns;

    /**
     * If set to true,
     * will throw a MissingDataException if there is incomplete rows remaining
     * when all input iterators are fully consumed and closed.
     *
     * @var bool
     */
    protected $strict = true;

    /** @var array[] */
    protected $data;

    /**
     * Properties that can be set via the options method.
     *
     * @var array
     */
    protected $availableOptions = [
        'index',
        'columns',
        'strict'
    ];

    /**
     * @return \Generator<Row>
     *
     * @throws MissingDataException
     */
    public function extract(): \Generator
    {
        // consume input iterators
        do {
            foreach ($this->input as $iterator) {
                /** @var \Iterator $iterator */
                if (
                    ($line = $iterator->current())
                    && ($row = $this->build($line))
                ) {
                    yield new Row($row);
                }
                $iterator->next();
            }
        } while (
            $this->hasValidInput()
        );

        if ($this->strict && \count($this->data)) {
            throw new MissingDataException(
                sprintf(
                    'Missing data for the rows: %s',
                    \json_encode(
                        array_values($this->data),
                        JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_UNICODE
                    )
                )
            );
        }

        // return incomplete remaining rows
        foreach ($this->data as $row) {
            yield new Row($row);
        }
    }

    /**
     * Accumulate row data and return when completed
     *
     * @param mixed[] $line
     *
     * @return mixed[]
     */
    protected function build(array $line): ?array
    {
        $hash = $this->lineHash($line);

        $this->data[$hash] = array_merge(
            $this->data[$hash] ?? [],
            $line
        );

        if ($this->isCompleted($hash)) {
            $row = $this->data[$hash];
            unset($this->data[$hash]); # free the RAM

            return $row;
        }

        return null;
    }

    /**
     * Check if row is completed
     */
    protected function isCompleted(string $hash): bool
    {
        return
            false === ((bool) array_diff(
                $this->columns,
                array_keys($this->data[$hash])
            ));
    }

    /**
     * Check if there is any opened iterators left
     */
    protected function hasValidInput(): bool
    {
        return 0 < \count(
            array_filter(
                $this->input,
                function (\Iterator $iterator): bool {
                    return $iterator->valid();
                }
            )
        );
    }

    /**
     * calculate row hash key from specified index array
     */
    protected function lineHash(array $line): string
    {
        return \json_encode(
            \array_map(
                function (string $key) use ($line) {
                    return $line[$key];
                },
                $this->index
            )
        );
    }
}
