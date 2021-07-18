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

use function json_encode;

/**
 * Exception thrown when the execution of a statement fails.
 *
 * @property-read Statement $statement
 * @property-read mixed[] $args
 */
class StatementInvocationFailed extends LogicException implements Exception
{
    /**
     * @uses get_statement
     * @uses get_args
     */
    use AccessorTrait;

    private function get_statement(): Statement
    {
        return $this->statement;
    }

    /**
     * @return mixed[]
     */
    private function get_args(): array
    {
        return $this->args;
    }

    /**
     * @param mixed[] $args
     */
    public function __construct(
        private Statement $statement,
        private array $args,
        string $message = null,
        Throwable $previous = null
    ) {
        parent::__construct($message ?: $this->format_message($statement, $args), 0, $previous);
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
