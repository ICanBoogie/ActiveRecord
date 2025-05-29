<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `created_at` property.
 */
trait CreatedAtProperty
{
    public DateTime $created_at {
        get => $this->created_at ??= DateTime::none();
        set(\DateTimeInterface|DateTime|string|null $value) => DateTimePropertySupport::adapt($value);
    }
}
