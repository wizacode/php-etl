<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @copyright   Copyright (c) Leonardo Marquine
 * @license     MIT
 */

declare(strict_types=1);

namespace Tests\Loaders;

use Tests\TestCase;
use Wizaplace\Etl\Loaders\CsvLoader;
use Wizaplace\Etl\Row;

class CsvLoaderTest extends TestCase
{
    /** @var string|false|mixed */
    protected $outputPath;

    /** @var CsvLoader */
    private $csvLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $path = tempnam('/tmp', 'phpunit_');
        if (false === $path) {
            static::fail('Could not create temp file');
        }
        $this->outputPath = "{$path}.csv";

        $this->csvLoader = new CsvLoader();
        $this->csvLoader->output($this->outputPath);
        $this->csvLoader->initialize();
    }

    /**
     * Test CSV loading with 2 rows and no options
     */
    public function testLoadCsvNoOption(): void
    {
        $row1 = $this->productRowFactory('Table', 10.5, 'A simple table');
        $row2 = $this->productRowFactory('Chair', 305.75, 'A \"deluxe chair\". You need it!');

        $this->csvLoader->load($row1);
        $this->csvLoader->load($row2);
        $this->csvLoader->finalize();

        // Opening generated file
        $handle = fopen($this->outputPath, 'r');

        $line = fgets($handle);
        static::assertEquals('"Product name";Price;Description', trim($line));

        $line = fgets($handle);
        static::assertEquals('Table;10.5;"A simple table"', trim($line));

        $line = fgets($handle);
        static::assertEquals('Chair;305.75;"A \"deluxe chair\". You need it!"', trim($line));
    }

    /**
     * Test CSV loading with 2 rows and custom options
     */
    public function testLoadCsvCustomOptions(): void
    {
        $row1 = $this->productRowFactory('Table', 10.5, 'A simple table');
        $row2 = $this->productRowFactory('Chair', 305.75, 'A #|deluxe chair#|. You need it!');

        // Custom options
        $this->csvLoader->options(['delimiter' => ',', 'enclosure' => '|', 'escapeChar' => '#']);
        $this->csvLoader->load($row1);
        $this->csvLoader->load($row2);
        $this->csvLoader->finalize();

        // Opening generated file
        $handle = fopen($this->outputPath, 'r');

        $line = fgets($handle);
        static::assertEquals('|Product name|,Price,Description', trim($line));

        $line = fgets($handle);
        static::assertEquals('Table,10.5,|A simple table|', trim($line));

        $line = fgets($handle);
        static::assertEquals('Chair,305.75,|A #|deluxe chair#|. You need it!|', trim($line));
    }

    /** @dataProvider provideGetFileUriData */
    public function testGetFileUri(
        string $output,
        string $fileUri,
        int $linePerFile,
        int $fileCounter
    ): void {
        $loaderR = new \ReflectionClass(
            CsvLoader::class
        );

        $linePerFileR = $loaderR->getProperty('linePerFile');
        $linePerFileR->setAccessible(true);

        $fileCounterR = $loaderR->getProperty('fileCounter');
        $fileCounterR->setAccessible(true);

        $getFileUriR = $loaderR->getMethod('getFileUri');
        $getFileUriR->setAccessible(true);

        $this->csvLoader->output($output);
        $linePerFileR->setValue(
            $this->csvLoader,
            $linePerFile
        );
        $fileCounterR->setValue(
            $this->csvLoader,
            $fileCounter
        );

        static::assertEquals(
            $fileUri,
            $getFileUriR->invoke($this->csvLoader)
        );
    }

    public function provideGetFileUriData(): array
    {
        return [
            'Unique file without extension' => [
                'output' => 'relative/path/to/a/file',
                'fileUri' => 'relative/path/to/a/file',
                'linePerFile' => -1,
                'fileCounter' => 1,
            ],
            'Unique file with extension' => [
                'output' => '/hello/world.tsv',
                'fileUri' => '/hello/world.tsv',
                'linePerFile' => -1,
                'fileCounter' => 1,
            ],
            'Multiple files with extension' => [
                'output' => '/bye/people',
                'fileUri' => '/bye/people_42',
                'linePerFile' => 1,
                'fileCounter' => 42,
            ],
            'Multiple relative path files without extension' => [
                'output' => 'AFILE.CSV',
                'fileUri' => './AFILE_42.CSV',
                'linePerFile' => 1,
                'fileCounter' => 42,
            ]
        ];
    }

    /**
     * Test CSV loading with 3 rows and 1 row per file
     */
    public function testLoadCsvMultipleFiles(): void
    {
        // 1 line per file
        $this->csvLoader->options(['linePerFile' => 1]);
        $this->csvLoader->initialize();

        \array_map(
            fn(Row $row) => $this->csvLoader->load($row),
            [
                $this->productRowFactory('Table', 10.50, 'A simple table'),
                $this->productRowFactory('Chair', 305.75, 'A "deluxe chair". You need it!'),
                $this->productRowFactory('Desk', 12.2, 'Basic, really boring.'),
            ]
        );

        $this->csvLoader->finalize();

        $expectedResults = [
            1 => 'Table;10.5;"A simple table"',
            2 => 'Chair;305.75;"A ""deluxe chair"". You need it!"',
            3 => 'Desk;12.2;"Basic, really boring."',
        ];

        [
            'dirname' => $dirname,
            'filename' => $filename,
            'extension' => $extension,
        ] = \pathinfo($this->outputPath);

        // We should have 3 files
        for ($i = 1; $i <= 3; $i++) {
            $handle = fopen(
                "{$dirname}/{$filename}_{$i}.{$extension}",
                'r'
            );

            $line = fgets($handle);
            static::assertEquals('"Product name";Price;Description', trim($line));

            $line = fgets($handle);
            static::assertEquals($expectedResults[$i], trim($line));
        }
    }

    /**
     * Returning new row for testing
     */
    private function productRowFactory(string $name, float $price, string $description): Row
    {
        return new Row(
            [
                'Product name' => $name,
                'Price' => $price,
                'Description' => $description,
            ]
        );
    }
}
