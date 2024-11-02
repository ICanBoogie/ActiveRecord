<?php

namespace ICanBoogie\ActiveRecord;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a connection cannot be established.
 */
class ConnectionNotEstablished extends RuntimeException implements Exception
{
    public function __construct(
        public readonly string $id,
        string $message,
        Throwable $previous = null
    ) {
        parent::__construct($message, previous: $previous);
    }
}
