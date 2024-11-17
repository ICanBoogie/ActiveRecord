<?php

namespace ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord;
use LogicException;

/**
 * Provides a {@see Model} instance.
 */
final class StaticModelProvider
{
    /**
     * @var (Closure():ModelProvider)|null
     */
    private static ?Closure $factory = null;

    private static ?ModelProvider $provider = null;

    /**
     * Sets the {@see ModelProvider} factory.
     *
     * @param (callable():ModelProvider) $factory
     *     The factory is invoked once: the first time {@see model_for_record} is invoked.
     *
     * @return (callable():ModelProvider)|null
     *     The previous factory, or `null` if none was defined.
     */
    public static function set(callable $factory): ?callable
    {
        $previous = self::$factory;

        self::$factory = $factory(...);
        self::$provider = null;

        return $previous;
    }

    /**
     * Returns the current {@see ModelProvider} factory.
     *
     * @return (callable():ModelProvider)|null
     */
    public static function get(): ?callable
    {
        return self::$factory;
    }

    /**
     * Unsets the {@see ModelProvider} factory.
     */
    public static function reset(): void
    {
        self::$factory = null;
        self::$provider = null;
    }

    /**
     * Returns the Model for an ActiveRecord.
     *
     * @template T of ActiveRecord
     *
     * @param class-string<T> $activerecord_class
     *
     * @return Model<T>
     **/
    public static function model_for_record(string $activerecord_class): Model
    {
        $factory = self::$factory
            ?? throw new LogicException(
                "No factory defined yet. Please define one with `StaticModelProvider::set()`"
            );

        return (self::$provider ??= $factory())->model_for_record($activerecord_class);
    }

    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
