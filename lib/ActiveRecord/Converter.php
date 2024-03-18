<?php

namespace ICanBoogie\ActiveRecord;

/**
 * @template T
 * @template U
 */
interface Converter
{
    /**
     * Converts a value from the database.
     *
     * @param T $value
     *
     * @return U
     */
    public function from(mixed $value): mixed;

    /**
     * Converts a value to the database.
     *
     * @param U $value
     *
     * @return T
     */
    public function to(mixed $value): mixed;
}
