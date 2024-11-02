<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `date` property.
 *
 * @property DateTime $date
 *
 * @codeCoverageIgnore
 */
trait DateProperty
{
    /**
     * The date.
     *
     * @var mixed
     */
    private $date;

    /**
     * Returns the date.
     */
    protected function get_date(): DateTime
    {
        return DateTimePropertySupport::get($this->date);
    }

    /**
     * Sets the date.
     *
     * @param mixed $date
     */
    protected function set_date($date): void
    {
        DateTimePropertySupport::set($this->date, $date);
    }
}
