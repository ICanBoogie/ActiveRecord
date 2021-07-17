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

/**
 * Exception thrown when the execution of a statement fails.
 *
 * @property-read Statement $statement
 * @property-read array $args
 */
class StatementInvocationFailed extends \LogicException implements Exception
{
    /**
     * @uses get_statement
     * @uses get_args
     */
    use AccessorTrait;

    /**
     * @var Statement
     */
    private $statement;

    private function get_statement(): Statement
    {
        return $this->statement;
    }

    /**
     * @var array
     */
    private $args;

    private function get_args(): array
    {
        return $this->args;
    }

    public function __construct(
        Statement $statement,
        array $args,
        string $message = null,
        int $code = 500,
        \Throwable $previous = null
    ) {
        $this->statement = $statement;
        $this->args = $args;

        parent::__construct($message ?: $this->format_message($statement, $args), $code, $previous);
    }

    /**
     * Formats a message from a statement and its arguments.
     *
     * @param array<string, mixed> $args
     */
    private function format_message(Statement $statement, array $args): string
    {
        return "Statement execution failed: {$statement->queryString}, with: " . \json_encode($args);
    }
}
