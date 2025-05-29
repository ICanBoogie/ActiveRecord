<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `updated_at` property.
 */
trait UpdatedAtProperty
{
    public DateTime $updated_at {
        get => $this->updated_at ??= DateTime::none();
        set(\DateTimeInterface|DateTime|string|null $value) => DateTimePropertySupport::adapt($value);
    }
}
