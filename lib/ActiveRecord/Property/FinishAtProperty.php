<?php

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\DateTime;

/**
 * Implements a `finish_at` property.
 */
// @phpstan-ignore trait.unused (provided to applications)
trait FinishAtProperty
{
    public DateTime $finish_at {
        get => $this->finish_at ??= DateTime::none();
        set(\DateTimeInterface|DateTime|string|null $value) => DateTimePropertySupport::adapt($value);
    }
}
