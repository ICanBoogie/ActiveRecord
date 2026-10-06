<?php

namespace ICanBoogie\ActiveRecord\Schema;

/**
 * A custom columns can implement this interface to resolve into a more basic one.
 */
interface ResolvesToColumn
{
    /**
     * Resolves the custom column into a more basic one.
     */
    public function resolve(): Column;
}
