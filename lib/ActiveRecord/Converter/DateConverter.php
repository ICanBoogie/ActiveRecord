<?php

namespace ICanBoogie\ActiveRecord\Converter;

use ICanBoogie\ActiveRecord\Converter;
use ICanBoogie\DateTime\LocalDate;

/**
 * @implements Converter<string, LocalDate>
 */
final class DateConverter implements Converter
{
    public function from(mixed $value): LocalDate
    {
        return LocalDate::from($value);
    }

    public function to(mixed $value): string
    {
        assert($value instanceof LocalDate);

        return (string) $value;
    }
}
