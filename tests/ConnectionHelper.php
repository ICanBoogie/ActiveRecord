<?php

namespace ICanBoogie;

use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\ConnectionOptions as Options;

trait ConnectionHelper
{
	static public function get_connection()
	{
		return new Connection('sqlite::memory:', null, null, [

			Options::ID => 'helper',
			Options::CHARSET_AND_COLLATE => 'ascii/bin',
			Options::TIMEZONE => '+02:30'

		]);
	}
}
