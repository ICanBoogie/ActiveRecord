<?php

namespace ICanBoogie\ActiveRecord;

use LogicException;
use Throwable;

use function json_encode;

/**
 * Exception thrown when the execution of a statement fails.
 */
class StatementInvocationFailed extends LogicException implements Exception
{
    /**
     * @param mixed[] $args
     */
    public function __construct(
        public readonly Statement $statement,
        public readonly array $args,
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?? $this->format_message($statement, $args), previous: $previous);
    }

    /**
     * Formats a message from a statement and its arguments.
     *
     * @param mixed[] $args
     */
    private function format_message(Statement $statement, array $args): string
    {
        return "Statement execution failed: {$statement->pdo_statement->queryString}, with: " . json_encode($args);
    }
}
