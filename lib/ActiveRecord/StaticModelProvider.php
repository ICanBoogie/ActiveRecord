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
final class StaticModelProvider
{
    /**
     * @var (Closure(): ModelProvider)|null
     */
    private static Closure|null $proxy = null;

    /**
     * Defines {@link Model} resolver proxy.
     *
     * @param (callable(): ModelProvider) $proxy
     *
     * @return (callable(): ModelProvider)|null The previous proxy, or `null` if none was defined.
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
     * @return (callable(): ModelProvider)|null
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
     * Returns the Model for an ActiveRecord.
     *
     * @template T of ActiveRecord
     *
     * @param class-string<T> $activerecord_class
     *
     * @phpstan-return Model<int|non-empty-string|non-empty-string[], T>
     **/
    public static function model_for_record(string $activerecord_class): Model
    {
        $proxy = self::$proxy
            ?? throw new LogicException(
                "No resolver proxy is defined yet. Please define one with `StaticModelProvider::define()`."
            );

        return $proxy()->model_for_record($activerecord_class);
    }
}
