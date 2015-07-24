<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Driver;
use ICanBoogie\DateTime;

/**
 * Basic connection driver.
 *
 * @property-read Connection $connection
 */
abstract class BasicDriver implements Driver
{
	use AccessorTrait;

	/**
	 * @var callable
	 */
	private $connection_provider;

	/**
	 * @return Connection
	 */
	protected function get_connection()
	{
		$connection_provider = $this->connection_provider;

		return $connection_provider();
	}

	/**
	 * @param callable $connection_provider A callable that provides a database connection.
	 */
	public function __construct(callable $connection_provider)
	{
		$this->connection_provider = $connection_provider;
	}

	/**
	 * @inheritdoc
	 */
	public function quote_string($string)
	{
		$connection = $this->connection;

		if (is_array($string))
		{
			return array_map(function($v) use($connection) {

				return $connection->quote($v);

			}, $string);
		}

		return $connection->quote($string);
	}

	/**
	 * @inheritdoc
	 */
	public function quote_identifier($identifier)
	{
		$quote = '`';

		if (is_array($identifier))
		{
			return array_map(function($v) use ($quote) {

				return $quote . $v . $quote;

			}, $identifier);
		}

		return $quote . $identifier . $quote;
	}

	/**
	 * @inheritdoc
	 */
	public function cast_value($value, $type = null)
	{
		if ($value instanceof \DateTime)
		{
			$value = DateTime::from($value);

			return $value->utc->as_db;
		}

		if ($value === false)
		{
			return 0;
		}

		if ($value === true)
		{
			return 1;
		}

		return $value;
	}

	/**
	 * Returns table name, including possible prefix.
	 *
	 * @param string $unprefixed_table_name
	 *
	 * @return string
	 */
	protected function resolve_table_name($unprefixed_table_name)
	{
		return $this->connection->table_name_prefix . $unprefixed_table_name;
	}

	/**
	 * Returns quoted table name, including possible prefix.
	 *
	 * @param string $unprefixed_table_name
	 *
	 * @return string
	 */
	protected function resolve_quoted_table_name($unprefixed_table_name)
	{
		return $this->quote_identifier($this->connection->table_name_prefix . $unprefixed_table_name);
	}
}
