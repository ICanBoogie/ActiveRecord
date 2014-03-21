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
	 * @return ActiveRecord\Connections
	 */
	static public function core_lazy_get_connections(Core $core)
	{
		return new Connections($core->configs['activerecord_connections']);
	}

	/**
	 * Getter for the "primary" database connection.
	 *
	 * @return Database
	 */
	static public function core_lazy_get_db(Core $core)
	{
		return $core->connections['primary'];
	}
}