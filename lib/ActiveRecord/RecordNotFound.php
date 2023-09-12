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

use ICanBoogie\ActiveRecord;
use LogicException;
use Throwable;

/**
 * Exception thrown when one or several records cannot be found.
 *
 * @property-read ActiveRecord[] $records
 */
class RecordNotFound extends LogicException implements Exception
{
    /**
     * @param array<int|non-empty-string, ?ActiveRecord> $records
     *     Where _key_ is a primary key.
     */
    public function __construct(
        string $message,
        public readonly array $records = [],
        Throwable $previous = null
    ) {
        parent::__construct($message, previous: $previous);
    }
}
