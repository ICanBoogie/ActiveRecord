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
 * Connection driver interface.
 */
interface Driver
{
	/**
	 * Quotes a string, or an array of strings.
	 *
	 * @param string|array $string
	 *
	 * @return string
	 */
	public function quote_string($string);

	/**
	 * Quotes an identifier, or an array of identifiers.
	 *
	 * @param string|array $identifier
	 *
	 * @return string
	 */
	public function quote_identifier($identifier);

	/**
	 * Casts a value into a database compatible representation.
	 *
	 * @param mixed $value
	 * @param string|null $type One of `SchemaColumn::TYPE_*`.
	 *
	 * @return mixed
	 */
	public function cast_value($value, $type = null);

	/**
	 * Creates a table given a schema.
	 *
	 * @param string $unprefixed_table_name
	 * @param Schema $schema
	 *
	 * @return $this
	 *
	 * @throws \Exception
	 */
	public function create_table($unprefixed_table_name, Schema $schema);

	/**
	 * Creates indexes given a schema.
	 *
	 * @param string $unprefixed_table_name
	 * @param Schema $schema
	 *
	 * @return $this
	 *
	 * @throws \Exception
	 */
	public function create_indexes($unprefixed_table_name, Schema $schema);

	/**
	 * Creates unique indexes given a schema.
	 *
	 * @param string $unprefixed_table_name
	 * @param Schema $schema
	 *
	 * @return $this
	 *
	 * @throws \Exception
	 */
	public function create_unique_indexes($unprefixed_table_name, Schema $schema);

	/**
	 * Checks if a specified table exists in the database.
	 *
	 * @param string $unprefixed_name The unprefixed name of the table.
	 *
	 * @return bool `true` if the table exists, `false` otherwise.
	 */
	public function table_exists($unprefixed_name);

	/**
	 * Optimizes the tables of the database.
	 */
	public function optimize();
}
