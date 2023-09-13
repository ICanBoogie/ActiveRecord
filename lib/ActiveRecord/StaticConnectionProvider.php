<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use Closure;
use LogicException;

/**
 * Provides a {@link Connection} instance.
 */
final class StaticConnectionProvider
{
    /**
     * @var (Closure(): ConnectionProvider)|null
     */
    private static ?Closure $factory = null;
    private static ?ConnectionProvider $provider = null;

    /**
     * Defines the {@link ConnectionProvider} factory.
     *
     * @param (callable(): ConnectionProvider) $factory
     *     The factory is invoked once: the first time {@link connection_for_id} is invoked.
     *
     * @return (callable(): ConnectionProvider)|null
     *     The previous factory, or `null` if none was defined.
     */
    public static function define(callable $factory): ?callable
    {
        $previous = self::$factory;

        self::$factory = $factory(...);
        self::$provider = null;

        return $previous;
    }

    /**
     * Returns the current {@link ConnectionProvider} factory.
     *
     * @return (callable(): ConnectionProvider)|null
     */
    public static function defined(): ?callable
    {
        return self::$factory;
    }

    /**
     * Undefines the {@link ConnectionProvider} factory.
     */
    public static function undefine(): void
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
                "No factory defined yet. Please define one with `StaticConnectionProvider::define()`"
            );

        return (self::$provider ??= $factory())->connection_for_id($id);
    }
}
