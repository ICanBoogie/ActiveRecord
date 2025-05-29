<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Provides support for datetime properties.
 */
class DateTimePropertySupport
{
    public static function adapt(\DateTimeInterface|DateTime|string|null $datetime): DateTime
    {
        if ($datetime === null) {
            return DateTime::none();
        }

        if ($datetime instanceof DateTime) {
            return $datetime;
        }

        return DateTime::from($datetime);
    }
}
