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
 * Returns the requested model.
 *
 * @param string $id Model identifier.
 *
 * @return Model
 */
function get_model($id)
{
	return Helpers::get_model($id);
}

/**
 * Stores an {@link \ICanBoogie\ActiveRecord} instance in an application cache.
 *
 * @param Model $model
 * @param \ICanBoogie\ActiveRecord $record
 */
function cache_store(Model $model, \ICanBoogie\ActiveRecord $record)
{
	Helpers::cache_store($mode, $record);
}

/**
 * Retrieves an {@link \ICanBoogie\ActiveRecord} instance from an application cache.
 *
 * @param Model $model
 * @param int $key
 *
 * @return \ICanBoogie\ActiveRecord
 */
function cache_retrieve(Model $model, $key)
{
	return Helpers::cache_retrieve($model, $key);
}

/**
 * Eliminates an {@link \ICanBoogie\ActiveRecord} instance from an application cache.
 *
 * @param Model $model
 * @param int $key
 */
function cache_eliminate(Model $model, $key)
{
	Helpers::cache_eliminate($model, $key);
}

/**
 * Creates a unique cache key with the specified model and key.
 *
 * @param Model $model
 * @param int $key
 *
 * @return string
 */
function create_cache_key(Model $model, $key)
{
	return Helpers::create_cache_key($model, $key);
}

/**
 * Patchable helpers of the ActiveRecord package.
 */
class Helpers
{
	static private $jumptable = array
	(
		'get_model' => array(__CLASS__, 'get_model'),
		'cache_store' => array(__CLASS__, 'cache_store'),
		'cache_retrieve' => array(__CLASS__, 'cache_retrieve'),
		'cache_eliminate' => array(__CLASS__, 'cache_eliminate'),
		'create_cache_key' => array(__CLASS__, 'create_cache_key')
	);

	/**
	 * Calls the callback of a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param array $arguments Arguments.
	 *
	 * @return mixed
	 */
	static public function __callstatic($name, array $arguments)
	{
		return call_user_func_array(self::$jumptable[$name], $arguments);
	}

	/**
	 * Patches a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param collable $callback Callback.
	 *
	 * @throws \RuntimeException is attempt to patch an undefined function.
	 */
	static public function patch($name, $callback)
	{
		if (empty(self::$jumptable[$name]))
		{
			throw new \RuntimeException("Undefined patchable: $name.");
		}

		self::$jumptable[$name] = $callback;
	}

	/*
	 * Default implementations
	 */

	static private function get_model($id)
	{
		throw new \RuntimeException("The function " . __FUNCTION__ . "() needs to be patched.");
	}

	static private $cached_records = array();

	static private function cache_store(Model $model, \ICanBoogie\ActiveRecord $record)
	{

	}

	static private function cache_retrieve(Model $model, $key)
	{

	}

	static private function cache_eliminate(Model $model, $key)
	{

	}

	static private function create_cache_key(Model $model, $key)
	{
		if ($key === null)
		{
			throw new \InvalidArgumentException('key is null.');
		}

		return $model->connection->id . '/' . $model->name . '/' . $key;
	}
}