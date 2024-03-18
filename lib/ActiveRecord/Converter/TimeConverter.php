<?php

namespace ICanBoogie\ActiveRecord\Converter;

use ICanBoogie\ActiveRecord\Converter;
use ICanBoogie\DateTime\LocalDate;
use ICanBoogie\DateTime\LocalTime;

/**
 * @implements Converter<string, LocalTime>
 */
final class TimeConverter implements Converter
{
    public function from(mixed $value): LocalTime
    {
        return LocalTime::from($value);
    }

    public function to(mixed $value): string
    {
        assert($value instanceof LocalTime);

        return (string) $value;
    }
}
