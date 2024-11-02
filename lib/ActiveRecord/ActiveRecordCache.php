<?php

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
