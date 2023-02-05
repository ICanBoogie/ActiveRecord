<?php

namespace ICanBoogie\ActiveRecord;

use InvalidArgumentException;

class UnsetAttribute extends InvalidArgumentException
{
    /**
     * @param array<string, mixed> $attributes
     */
    public static function ThrowIf(array $attributes, string $attribute): void
    {
        if (isset($attributes[$attribute])) {
            return;
        }

        throw new UnsetAttribute("Unset attribute: $attribute.");
    }
}
