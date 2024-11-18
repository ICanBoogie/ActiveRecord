<?php

namespace ICanBoogie\ActiveRecord;

final readonly class Inflector
{
    private static function get(): \ICanBoogie\Inflector
    {
        static $inflector;

        return $inflector ??= \ICanBoogie\Inflector::get();
    }

    public static function singularize(string $word): string
    {
        return self::get()->singularize($word);
    }

    public static function pluralize(string $word): string
    {
        return self::get()->pluralize($word);
    }

    public static function underscore(string $word): string
    {
        return self::get()->underscore($word);
    }
}
