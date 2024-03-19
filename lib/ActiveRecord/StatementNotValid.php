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
     * @param array<mixed> $args
     */
    public function __construct(
        public readonly string $statement,
        public readonly array $args = [],
        public readonly ?PDOException $original = null
    ) {
        parent::__construct($this->format_message($statement, $args, $original));
    }

    /**
     * @param array<mixed> $args
     */
    private function format_message(string $statement, array $args, PDOException $original = null): string
    {
        $message = '';

        if ($original) {
            $er = array_pad($original->errorInfo ?? [], 3, '');

            $message = sprintf('%s(%s) %s — ', $er[0], $er[1], $er[2]);
        }

        $message .= "`$statement`";

        if (count($args)) {
            $message .= " " . json_encode($args);
        }

        return $message;
    }
}
