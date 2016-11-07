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
	 * @var callable {@link Model} provider
	 */
	static private $provider;

	/**
	 * Defines the {@link Model} provider.
	 *
	 * @param callable $provider
	 *
	 * @return callable The previous provider, or `null` if none was defined.
	 */
	static public function define(callable $provider)
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
	static public function defined()
	{
		return self::$provider;
	}

	/**
	 * Undefine the provider.
	 */
	static public function undefine()
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
	static public function provide($id)
	{
		$provider = self::$provider;

		if (!$provider)
		{
			throw new \LogicException("No provider is defined yet. Please define one with `ModelProvider::define(\$provider)`.");
		}

		$model = $provider($id);

		if (!$model)
		{
			throw new ModelNotDefined($id);
		}

		return $model;
	}
}
