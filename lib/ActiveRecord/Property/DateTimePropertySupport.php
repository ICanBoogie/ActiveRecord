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
 * Provides support for datetime properties.
 */
class DateTimePropertySupport
{
    /**
     * Sets the datetime in a property.
     *
     * @param mixed $property Reference to the property to set.
     * @param \DateTimeInterface|string $datetime Date and time.
     */
    public static function set(&$property, $datetime): void
    {
        $property = $datetime === 'now' ? DateTime::now() : $datetime;
    }

    /**
     * Returns the {@link DateTime} instance of a property.
     *
     * @param mixed $property Reference to the property to return.
     */
    public static function get(&$property): DateTime
    {
        if ($property instanceof DateTime) {
            return $property;
        }

        return $property = $property === null ? DateTime::none() : new DateTime($property, 'utc');
    }

    /**
     * @param mixed $property Reference to the property to ensure.
     * @param \DateTimeInterface|string $datetime
     */
    public static function ensureNotEmpty(&$property, $datetime = 'now'): DateTime
    {
        if (!self::get($property)->is_empty) {
            return $property;
        }

        self::set($property, $datetime);

        return $property;
    }
}
