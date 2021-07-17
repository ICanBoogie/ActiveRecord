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
 * Implements a `created_at` property.
 *
 * @see DateTimeProperty
 *
 * @property DateTime $created_at
 */
trait CreatedAtProperty
{
    /**
     * The date and time at which the record was created.
     *
     * @var mixed
     */
    private $created_at;

    /**
     * Returns the date and time at which the record was created.
     */
    protected function get_created_at(): DateTime
    {
        return DateTimePropertySupport::get($this->created_at);
    }

    /**
     * Sets the date and time at which the record was created.
     *
     * @param mixed $datetime
     */
    protected function set_created_at($datetime): void
    {
        DateTimePropertySupport::set($this->created_at, $datetime);
    }
}
