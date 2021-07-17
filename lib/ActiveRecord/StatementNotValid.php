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
use PDOException;

/**
 * Exception thrown in attempt to execute a statement that is not valid.
 *
 * @property-read string $statement The invalid statement.
 * @property-read array $args The arguments of the statement.
 * @property-read PDOException|null $original The original exception.
 */
class StatementNotValid extends \RuntimeException implements Exception
{
    /**
     * @uses get_statement
     * @uses get_args
     * @uses get_original
     */
    use AccessorTrait;

    /**
     * @var string
     */
    private $statement;

    private function get_statement(): string
    {
        return $this->statement;
    }

    /**
     * @var array<string, mixed>
     */
    private $args;

    /**
     * @return array<string, mixed>
     */
    private function get_args(): array
    {
        return $this->args;
    }

    /**
     * @var PDOException|null
     */
    private $original;

    private function get_original(): ?PDOException
    {
        return $this->original;
    }

    /**
     * @param array|string $statement
     * @param int $code
     * @param PDOException|null $original
     */
    public function __construct($statement, int $code = 500, PDOException $original = null)
    {
        $args = [];

        if (\is_array($statement)) {
            [ $statement, $args ] = $statement;
        }

        $this->statement = $statement;
        $this->args = $args;
        $this->original = $original;

        parent::__construct($this->format_message($statement, $args, $original), $code);
    }

    private function format_message(string $statement, array $args, PDOException $previous = null): string
    {
        $message = null;

        if ($previous) {
            $er = \array_pad($previous->errorInfo, 3, '');

            $message = \sprintf('%s(%s) %s — ', $er[0], $er[1], $er[2]);
        }

        $message .= "`$statement`";

        if ($args) {
            $message .= " " . ($args ? \json_encode($args) : "[]");
        }

        return $message;
    }
}
