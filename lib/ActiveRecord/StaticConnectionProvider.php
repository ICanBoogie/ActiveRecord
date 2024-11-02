<?php

namespace ICanBoogie\ActiveRecord;

use Closure;
use LogicException;

/**
 * Provides a {@see Connection} instance.
 */
final class StaticConnectionProvider
{
    /**
     * @var (Closure(): ConnectionProvider)|null
     */
    private static ?Closure $factory = null;
    private static ?ConnectionProvider $provider = null;

    /**
     * Defines the {@see ConnectionProvider} factory.
     *
     * @param (callable(): ConnectionProvider) $factory
     *     The factory is invoked once: the first time {@link connection_for_id} is invoked.
     *
     * @return (callable(): ConnectionProvider)|null
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
     * Returns the current {@see ConnectionProvider} factory.
     *
     * @return (callable(): ConnectionProvider)|null
     */
    public static function get(): ?callable
    {
        return self::$factory;
    }

    /**
     * Resets the {@see ConnectionProvider} factory.
     */
    public static function reset(): void
    {
        self::$factory = null;
        self::$provider = null;
    }

    /**
     * @param non-empty-string $id
     *     A connection identifier.
     */
    public static function connection_for_id(string $id): Connection
    {
        $factory = self::$factory
            ?? throw new LogicException(
                "No factory defined yet. Please define one with `StaticConnectionProvider::set()`"
            );

        return (self::$provider ??= $factory())->connection_for_id($id);
    }
}
