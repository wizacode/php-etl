<?php

/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @author      Karl DeBisschop <kdebisschop@gmail.com>
 * @copyright   Copyright (c) Wizacha
 * @license     MIT
 */

declare(strict_types=1);

namespace Wizaplace\Etl\Transformers;

use Wizaplace\Etl\Row;

class Validator extends Transformer
{
    public const CALLBACK = 'callback';

    /**
     * The callback function.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Properties that can be set via the options method.
     *
     * @var string[]
     */
    protected array $availableOptions = [
        self::CALLBACK,
    ];

    /**
     * Transform the given row.
     */
    public function transform(Row $row): void
    {
        if (false === $this->getCallback()($row)) {
            $row->discard();
        }
    }

    private function getCallback(): callable
    {
        return $this->callback;
    }
}
