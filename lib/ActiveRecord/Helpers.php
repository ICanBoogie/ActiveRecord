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
 * Patchable helpers of the ActiveRecord package.
 *
 * @method static Model get_model(string $id) Returns the model with the corresponding identifier.
 */
class Helpers
{
	static private $mapping = [

		'get_model' => [ __CLASS__, 'default_get_model' ]

	];

	/**
	 * Calls the callback of a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param array $arguments Arguments.
	 *
	 * @return mixed
	 */
	static public function __callStatic($name, array $arguments)
	{
		$method = self::$mapping[$name];

		return $method(...$arguments);
	}

	/**
	 * Patches a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param callable $callback Callback.
	 *
	 * @throws \RuntimeException is attempt to patch an undefined function.
	 *
	 * @return callable
	 */
	static public function patch($name, $callback)
	{
		if (empty(self::$mapping[$name]))
		{
			throw new \LogicException("Undefined patchable: $name.");
		}

		$previous = self::$mapping[$name];
		self::$mapping[$name] = $callback;

		return $previous;
	}

	/*
	 * Default implementations
	 */

	/**
	 * @throws \LogicException
	 */
	static protected function default_get_model()
	{
		throw new \LogicException("The function `ICanBoogie\\ActiveRecord\\get_model()` needs to be patched.");
	}
}
