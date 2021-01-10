<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Loaders;

use Wizaplace\Etl\Exception\IoException;
use Wizaplace\Etl\Row;
use Wizaplace\Etl\Traits\FilePathTrait;

class CsvLoader extends Loader
{
    use FilePathTrait;

    /**
     * Count how many lines have been loaded
     *
     * @var int
     */
    protected $loaderCounter = 0;

    /**
     * Count how many files have been created
     *
     * @var int
     */
    protected $fileCounter = 1;

    /**
     * CSV file handler
     *
     * @var resource|bool
     */
    protected $fileHandler;

    /**
     * All available options for this loader
     *
     * @var string[]
     */
    protected $availableOptions = [
        'delimiter', 'enclosure', 'escapeChar', 'linePerFile',
    ];

    /**
     * The CSV delimiter string.
     *
     * @var string
     */
    protected $delimiter = ';';

    /**
     * The CSV enclosure string.
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * The CSV escaping string.
     *
     * @var string
     */
    protected $escapeChar = '\\';

    /**
     * Max lines per file
     *
     * @var int
     */
    protected $linePerFile = -1;

    public function initialize(): void
    {
        $this->openFile();
    }

    /**
     * Finalize the step.
     */
    public function finalize(): void
    {
        $this->closeFile();
    }

    /**
     * Load the given row.
     */
    public function load(Row $row): void
    {
        // If we reach the max lines, we open a new file
        if (
            0 < $this->linePerFile
            && $this->linePerFile <= $this->loaderCounter
        ) {
            $this->loaderCounter = 0;
            $this->fileCounter++;

            $this->closeFile();
            $this->openFile();
        }

        $rowArray = $row->toArray();

        if (0 === $this->loaderCounter) {
            $this->putCsv($this->getHeaders($rowArray));
        }

        $this->putCsv($rowArray);
        $this->loaderCounter++;
    }

    protected function getFileUri(): string
    {
        $pathinfo = \pathinfo($this->output);

        if (0 < $this->linePerFile) {
            $suffix = "_{$this->fileCounter}";
        } else {
            $suffix = '';
        }

        if (\array_key_exists('extension', $pathinfo)){
            $extension = ".{$pathinfo['extension']}";
        } else {
            $extension = '';
        }

        return (
            $pathinfo['dirname']
            . DIRECTORY_SEPARATOR
            . $pathinfo['filename']
            . $suffix
            . $extension
        );
    }

    /**
     * CSV headers generation
     *
     * @param array[]|string[] $rowArray
     *
     * @return string[]
     */
    protected function getHeaders(array $rowArray): array
    {
        return \array_keys($rowArray);
    }

    protected function openFile(): void
    {
        $fileUri = $this->getFileUri();
        $this->checkOrCreateDir($fileUri);
        $this->fileHandler = @\fopen($fileUri, 'w+');

        if (false === $this->fileHandler) {
            throw new IoException("Impossible to open the file '{$fileUri}'");
        }
    }

    protected function closeFile(): void
    {
        \fclose($this->fileHandler);
    }

    /**
     * Insert data into CSV file
     *
     * @param string[] $data
     */
    protected function putCsv(array $data): void
    {
        \fputcsv(
            $this->fileHandler,
            $data,
            $this->delimiter,
            $this->enclosure,
            $this->escapeChar
        );
    }
}
