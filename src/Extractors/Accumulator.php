<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     MIT
 */

namespace Wizaplace\Etl\Extractors;

use Wizaplace\Etl\DirtyRow;
use Wizaplace\Etl\Exception\IncompleteDataException;
use Wizaplace\Etl\Exception\InvalidOptionException;
use Wizaplace\Etl\Exception\UndefinedIndexException;
use Wizaplace\Etl\Row;

class Accumulator extends Extractor
{
    /**
     * The matching key tuplet between iterators.
     *
     * @var string[]
     */
    protected $index;

    /**
     * Columns.
     *
     * @var string[]
     */
    protected $columns;

    /**
     * If set to true,
     * will throw a MissingDataException if there is any incomplete rows remaining
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
        'strict',
    ];

    /**
     * Properties that MUST be set via the options method.
     *
     * @var array
     */
    protected $requiredOptions = [
        'index',
        'columns',
    ];

    /**
     * @return \Generator<Row>
     *
     * @throws IncompleteDataException
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
            throw new IncompleteDataException(
                \sprintf(
                    'Missing data for the rows: %s',
                    \json_encode(
                        \array_values($this->data),
                        JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_UNICODE
                    )
                )
            );
        }

        // then yield the incomplete remaining rows
        foreach ($this->data as $row) {
            yield new DirtyRow($row);
        }
    }

    /**
     * Accumulate row data and return when completed.
     *
     * @param mixed[] $line
     *
     * @return mixed[]
     */
    protected function build(array $line): ?array
    {
        try {
            $hash = $this->lineHash($line);
        } catch (UndefinedIndexException $exception) {
            return null;
        };

        $this->data[$hash] = \array_merge(
            $this->data[$hash] ?? [],
            $line
        );

        if ($this->isCompleted($hash)) {
            $row = $this->data[$hash];
            unset($this->data[$hash]); // free the RAM

            return $row;
        }

        return null;
    }

    /**
     * Check if row is completed.
     */
    protected function isCompleted(string $hash): bool
    {
        try {
            return false === (bool) \array_diff(
                $this->columns,
                \array_keys($this->data[$hash])
            );
        } catch (\Exception $exception) {
            throw new InvalidOptionException('specify at least 1 column', 3);
        }
    }

    /**
     * Check if there is any opened iterators left.
     */
    protected function hasValidInput(): bool
    {
        return 0 < \count(
            \array_filter(
                $this->input,
                function (\Iterator $iterator): bool {
                    return $iterator->valid();
                }
            )
        );
    }

    /**
     * calculate row hash key from specified index array.
     */
    protected function lineHash(array $line): string
    {
        if (!is_array($this->index)) {
            throw new InvalidOptionException('We need an array', 1);
        }

        if (1 < \count($this->index)) {
            throw new InvalidOptionException('Specify at least 1 index', 2);
        }

        return \json_encode(
            \array_map(
                function (string $key) use ($line) {
                    if (!array_key_exists($key, $line)) {
                        throw new UndefinedIndexException("Index $key not matching");
                    }

                    return $line[$key];
                },
                $this->index
            )
        );
    }
}
