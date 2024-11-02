<?php

namespace ICanBoogie\ActiveRecord;

use LogicException;
use Throwable;

/**
 * Exception thrown in an attempt to get a connection that is not defined.
 */
class ConnectionNotDefined extends LogicException implements Exception
{
    public function __construct(
        public readonly string $id,
        ?Throwable $previous = null
    ) {
        parent::__construct($this->format_message($id), previous: $previous);
    }

    private function format_message(string $id): string
    {
        return "Connection not defined: $id";
    }
}
