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
 * Exception thrown when there is no driver defined for a given driver name.
 *
 * @property-read string $driver_name
 */
class DriverNotDefined extends LogicException implements Exception
{
    /**
     * @uses get_driver_name
     */
    use AccessorTrait;

    private function get_driver_name(): string
    {
        return $this->driver_name;
    }

    public function __construct(
        private string $driver_name,
        string $message = null,
        Throwable $previous = null
    ) {
        parent::__construct($message ?? $this->format_message($driver_name), 0, $previous);
    }

    private function format_message(string $driver_name): string
    {
        return "Driver not defined for: $driver_name.";
    }
}
