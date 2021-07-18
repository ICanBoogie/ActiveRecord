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

/**
 * Interface for ActiveRecord cache.
 */
interface ActiveRecordCache
{
    /**
     * Stores an {@link ActiveRecord} instance in the cache.
     *
     * @param ActiveRecord $record
     */
    public function store(ActiveRecord $record): void;

    /**
     * Retrieves an {@link ActiveRecord} instance from the cache.
     */
    public function retrieve(string|int $key): ?ActiveRecord;

    /**
     * Eliminates an {@link ActiveRecord} instance from the cache.
     */
    public function eliminate(string|int $key): void;

    /**
     * Clears the cache.
     */
    public function clear(): void;
}
