<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `datetime` property.
 *
 * @property DateTime $datetime
 */
trait DateTimeProperty
{
    /**
     * The date and time.
     *
     * @var mixed
     */
    private $datetime;

    /**
     * Returns the date and time.
     */
    protected function get_datetime(): DateTime
    {
        return DateTimePropertySupport::get($this->datetime);
    }

    /**
     * Sets the date and time.
     *
     * @param mixed $datetime
     */
    protected function set_datetime($datetime): void
    {
        DateTimePropertySupport::set($this->datetime, $datetime);
    }
}
