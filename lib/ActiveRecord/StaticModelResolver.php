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
use ICanBoogie\ActiveRecord;
use LogicException;

/**
 * Provides a {@link Model} instance.
 */
final class StaticModelResolver
{
    /**
     * @var (Closure(): ModelResolver)|null
     */
    private static Closure|null $proxy = null;

    /**
     * Defines {@link Model} resolver proxy.
     *
     * @param (callable(): ModelResolver) $proxy
     *
     * @return (callable(): ModelResolver)|null The previous proxy, or `null` if none was defined.
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
     * @return (callable(): ModelResolver)|null
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
     * @param class-string<ActiveRecord>|ActiveRecord $class_or_activerecord
     */
    public static function model_for_activerecord(string|ActiveRecord $class_or_activerecord): Model
    {
        $proxy = self::$proxy
            ?? throw new LogicException(
                "No resolver proxy is defined yet. Please define one with `StaticModelResolver::define()`."
            );

        return $proxy()->model_for_activerecord($class_or_activerecord);
    }
}
