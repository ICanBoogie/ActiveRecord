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
