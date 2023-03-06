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
    private static Closure|null $proxy = null;

    /**
     * Defines {@link Connection} resolver proxy.
     *
     * @param (callable(): ConnectionProvider) $proxy
     *
     * @return (callable(): ConnectionProvider)|null The previous proxy, or `null` if none was defined.
     */
    public static function define(callable $proxy): ?callable
    {
        $previous = self::$proxy;

        self::$proxy = $proxy(...);

        return $previous;
    }

    /**
     * Returns the current resolver proxy.
     *
     * @return (callable(): ConnectionProvider)|null
     */
    public static function defined(): ?callable
    {
        return self::$proxy;
    }

    /**
     * Undefines the resolver proxy.
     */
    public static function undefine(): void
    {
        self::$proxy = null;
    }

    /**
     * @param string $id
     *     Connection identifier.
     */
    public static function connection_for_id(string $id): Connection
    {
        $proxy = self::$proxy
            ?? throw new LogicException(
                "No proxy defined yet. Please define one with `StaticConnectionProvider::define()`."
            );

        return $proxy()->connection_for_id($id);
    }
}
