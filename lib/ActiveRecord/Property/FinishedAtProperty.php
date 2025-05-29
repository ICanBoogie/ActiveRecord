<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `finished_at` property.
 */
trait FinishedAtProperty
{
    public DateTime $finished_at {
        get => $this->finished_at ??= DateTime::none();
        set(\DateTimeInterface|DateTime|string|null $value) => DateTimePropertySupport::adapt($value);
    }
}
