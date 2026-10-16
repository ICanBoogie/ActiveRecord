<?php

namespace ICanBoogie\ActiveRecord\Schema;

/**
 * A custom columns can implement this interface to turn into a more basic one.
 */
interface IntoColumn
{
    /**
     * Turns the custom column into a more basic one.
     */
    public function into_column(): Column;
}
