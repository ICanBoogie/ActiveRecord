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
