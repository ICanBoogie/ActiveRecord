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
 * Implements a `start_at` property.
 *
 * @see DateTimeProperty
 *
 * @property DateTime $start_at
 *
 * @codeCoverageIgnore
 */
trait StartAtProperty
{
    /**
     * The date and time at which the record was start.
     *
     * @var mixed
     */
    private $start_at;

    /**
     * Returns the date and time at which the record was start.
     */
    protected function get_start_at(): DateTime
    {
        return DateTimePropertySupport::get($this->start_at);
    }

    /**
     * Sets the date and time at which the record was start.
     *
     * @param mixed $datetime
     */
    protected function set_start_at($datetime): void
    {
        DateTimePropertySupport::set($this->start_at, $datetime);
    }
}
