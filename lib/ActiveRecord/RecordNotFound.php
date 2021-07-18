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
     * @uses get_records
     */
    use AccessorTrait;

    /**
     * @return ActiveRecord[]
     */
    private function get_records(): array
    {
        return $this->records;
    }

    /**
     * @param ActiveRecord[] $records
     */
    public function __construct(
        string $message,
        private array $records = [],
        Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
