<?php

namespace ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Exception;
use LogicException;
use Throwable;

/**
 * Thrown when a schema definition is invalid.
 */
class InvalidSchema extends LogicException implements Exception
{
    /**
     * @param string $message
     * @param class-string<\ICanBoogie\ActiveRecord>|null $record_class
     *     The record the schema is built for, if any. It is added to the message.
     */
    public function __construct(
        string $message,
        public readonly ?string $record_class = null,
        ?Throwable $previous = null,
    ) {
        if ($record_class) {
            $message .= " (record `$record_class`)";
        }

        parent::__construct($message, previous: $previous);
    }
}
