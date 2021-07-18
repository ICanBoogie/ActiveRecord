<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\Accessor\AccessorTrait;
use LogicException;
use Throwable;

/**
 * Exception thrown in attempt to set/unset the definition of an already instantiated model.
 *
 * @property-read string $id The identifier of the model.
 */
class ModelAlreadyInstantiated extends LogicException implements Exception
{
    /**
     * @uses get_id
     */
    use AccessorTrait;

    private function get_id(): string
    {
        return $this->id;
    }

    public function __construct(
        private string $id,
        Throwable $previous = null
    ) {
        parent::__construct($this->format_message($id), 0, $previous);
    }

    private function format_message(string $id): string
    {
        return "Model already instantiated: $id.";
    }
}
