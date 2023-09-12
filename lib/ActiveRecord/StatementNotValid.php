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

use PDOException;
use RuntimeException;

use function array_pad;
use function is_array;
use function json_encode;
use function sprintf;

/**
 * Exception thrown in attempt to execute a statement that is not valid.
 */
class StatementNotValid extends RuntimeException implements Exception
{
    /**
     * @var string
     */
    public readonly string $statement;

    /**
     * @var array<mixed>
     */
    public readonly array $args;

    /**
     * @param array{ string, array<mixed> }|string $statement
     */
    public function __construct(
        $statement,
        public readonly ?PDOException $original = null
    ) {
        $args = [];

        if (is_array($statement)) {
            [ $statement, $args ] = $statement;
        }

        $this->statement = $statement;
        $this->args = $args;

        parent::__construct($this->format_message($statement, $args, $original));
    }

    private function format_message(string $statement, array $args, PDOException $previous = null): string
    {
        $message = null;

        if ($previous) {
            $er = array_pad($previous->errorInfo, 3, '');

            $message = sprintf('%s(%s) %s — ', $er[0], $er[1], $er[2]);
        }

        $message .= "`$statement`";

        if ($args) {
            $message .= " " . ($args ? json_encode($args) : "[]");
        }

        return $message;
    }
}
