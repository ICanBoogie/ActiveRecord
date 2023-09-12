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

use Throwable;

use function ICanBoogie\format;

/**
 * Exception thrown when the fetch mode of a statement fails to be set.
 */
class UnableToSetFetchMode extends \RuntimeException implements Exception
{
    /**
     * @param mixed $mode
     */
    public function __construct(
        public readonly mixed $mode,
        string $message = null,
        Throwable $previous = null
    ) {
        parent::__construct($message ?? $this->format_message($mode), 0, $previous);
    }

    private function format_message(mixed $mode): string
    {
        return format("Unable to set fetch mode: %mode", [ 'mode' => $mode ]);
    }
}
