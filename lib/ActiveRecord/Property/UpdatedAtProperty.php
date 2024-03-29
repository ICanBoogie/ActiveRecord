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
 * Implements a `updated_at` property.
 *
 * @see DateTimeProperty
 *
 * @property DateTime $updated_at
 */
trait UpdatedAtProperty
{
    /**
     * The date and time at which the record was updated.
     *
     * @var mixed
     */
    private $updated_at;

    /**
     * Returns the date and time at which the record was updated.
     */
    protected function get_updated_at(): DateTime
    {
        return DateTimePropertySupport::get($this->updated_at);
    }

    /**
     * Sets the date and time at which the record was updated.
     *
     * @param mixed $datetime
     */
    protected function set_updated_at($datetime): void
    {
        DateTimePropertySupport::set($this->updated_at, $datetime);
    }
}
