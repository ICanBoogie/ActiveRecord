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

use ICanBoogie\Core;

class Hooks
{
	/**
	 * Synthesizes the `activerecord_connections` config from `activerecord` fragments.
	 *
	 * @param array $fragments
	 *
	 * @return array
	 */
	static public function synthesize_connections_config(array $fragments)
	{
		$config = [];

		foreach ($fragments as $fragment)
		{
			if (empty($fragment['connections']))
			{
				continue;
			}

			$config = array_merge($config, $fragment['connections']);
		}

		return $config;
	}

	/*
	 * Prototypes
	 */

	/**
	 * Returns the connections accessor.
	 *
	 * @return Connections
	 */
	static public function core_lazy_get_connections(Core $app)
	{
		return new Connections($app->configs['activerecord_connections'] ?: []);
	}

	/**
	 * Getter for the "primary" database connection.
	 *
	 * @return Connection
	 */
	static public function core_lazy_get_db(Core $app)
	{
		return $app->connections['primary'];
	}

	/**
	 * Returns the ActiveRecord cache associated with the model.
	 *
	 * @param Model $model
	 *
	 * @return \ICanBoogie\ActiveRecord\RunTimeActiveRecordCache
	 */
	static public function model_lazy_get_activerecord_cache(Model $model)
	{
		return new RunTimeActiveRecordCache($model);
	}
}
