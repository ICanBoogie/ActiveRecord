<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `date` property.
 */
trait DateProperty
{
    public DateTime $date {
        get => $this->date ??= DateTime::none();
        set(\DateTimeInterface|DateTime|string|null $value) => DateTimePropertySupport::adapt($value);
    }
}
