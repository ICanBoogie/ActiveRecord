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
    private static ?Closure $factory = null;

    private static ?ModelProvider $provider = null;

    /**
     * Sets the {@link ModelProvider} factory.
     *
     * @param (callable(): ModelProvider) $factory
     *     The factory is invoked once: the first time {@link model_for_record} is invoked.
     *
     * @return (callable(): ModelProvider)|null
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
     * Returns the current {@link ModelProvider} factory.
     *
     * @return (callable(): ModelProvider)|null
     */
    public static function get(): ?callable
    {
        return self::$factory;
    }

    /**
     * Unset the {@link ModelProvider} factory.
     */
    public static function unset(): void
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
     * @phpstan-return Model<int|non-empty-string|non-empty-string[], T>
     **/
    public static function model_for_record(string $activerecord_class): Model
    {
        $factory = self::$factory
            ?? throw new LogicException(
                "No factory defined yet. Please define one with `StaticModelProvider::define()`"
            );

        return (self::$provider ??= $factory())->model_for_record($activerecord_class);
    }
}
