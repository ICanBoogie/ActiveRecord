<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
