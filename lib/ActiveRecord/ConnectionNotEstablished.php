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
use RuntimeException;
use Throwable;

/**
 * Exception thrown when a connection cannot be established.
 *
 * @property-read string $id The identifier of the connection.
 */
class ConnectionNotEstablished extends RuntimeException implements Exception
{
    /**
     * @uses get_id
     */
    use AccessorTrait;

    private function get_id(): string
    {
        return $this->id;
    }

    public function __construct(
        private string $id,
        string $message,
        Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
