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

/**
 * Provides a {@link Model} instance.
 */
class ModelProvider
{
    /**
     * @var callable|null {@link Model} provider
     */
    private static $provider;

    /**
     * Defines the {@link Model} provider.
     *
     * @param callable $provider
     *
     * @return callable|null The previous provider, or `null` if none was defined.
     */
    public static function define(callable $provider): ?callable
    {
        $previous = self::$provider;

        self::$provider = $provider;

        return $previous;
    }

    /**
     * Returns the current provider.
     *
     * @return callable|null
     */
    public static function defined(): ?callable
    {
        return self::$provider;
    }

    /**
     * Undefine the provider.
     */
    public static function undefine(): void
    {
        self::$provider = null;
    }

    /**
     * Returns a {@link Model} instance using the provider.
     *
     * @param string $id Model identifier.
     *
     * @return Model
     *
     * @throws ModelNotDefined if the model cannot be provided.
     */
    public static function provide(string $id): Model
    {
        $provider = self::$provider;

        if (!$provider) {
            throw new \LogicException(
                "No provider is defined yet. Please define one with `ModelProvider::define(\$provider)`."
            );
        }

        $model = $provider($id);

        if (!$model) {
            throw new ModelNotDefined($id);
        }

        return $model;
    }
}
