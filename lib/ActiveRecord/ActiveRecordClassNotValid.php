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

use LogicException;
use Throwable;

use function ICanBoogie\format;

/**
 * Exception thrown when the ActiveRecord class is not valid.
 */
class ActiveRecordClassNotValid extends LogicException implements Exception
{
    public function __construct(
        public readonly string $class,
        string $message = null,
        Throwable $previous = null
    ) {
        parent::__construct($message ?? $this->format_message($class), previous: $previous);
    }

    private function format_message(string $class): string
    {
        return format("ActiveRecord class is not valid: %class", [

            'class' => $class

        ]);
    }
}
