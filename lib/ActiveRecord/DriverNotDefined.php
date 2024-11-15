<?php

namespace ICanBoogie\ActiveRecord;

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
     * @param non-empty-string $driver_name
     * @param non-empty-string|null $message
     */
    public function __construct(
        public readonly string $driver_name,
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
