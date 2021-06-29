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

use Throwable;

/**
 * Connection driver interface.
 */
interface Driver
{
	/**
	 * Quotes a string, or an array of strings.
	 *
	 * @param string|string[] $string
	 *
	 * @return string|string[]
	 */
	public function quote_string(string|array $string): string|array;

	/**
	 * Quotes an identifier, or an array of identifiers.
	 *
	 * @param string|string[] $identifier
	 *
	 * @return string|string[]
	 */
	public function quote_identifier(string|array $identifier): string|array;

	/**
	 * Casts a value into a database compatible representation.
	 *
	 * @param string|null $type One of `SchemaColumn::TYPE_*`.
	 */
	public function cast_value(mixed $value, string $type = null): mixed;

	/**
	 * Renders a column definition.
	 */
	public function render_column(SchemaColumn $column): string;

	/**
	 * Creates a table given a schema.
	 *
	 * @throws Throwable
	 */
	public function create_table(string $unprefixed_table_name, Schema $schema): void;

	/**
	 * Creates indexes given a schema.
	 *
	 * @throws Throwable
	 */
	public function create_indexes(string $unprefixed_table_name, Schema $schema): void;

	/**
	 * Creates unique indexes given a schema.
	 *
	 * @throws Throwable
	 */
	public function create_unique_indexes(string $unprefixed_table_name, Schema $schema): void;

	/**
	 * Checks if a specified table exists in the database.
	 *
	 * @param string $unprefixed_name The unprefixed name of the table.
	 *
	 * @return bool `true` if the table exists, `false` otherwise.
	 */
	public function table_exists(string $unprefixed_name): bool;

	/**
	 * Optimizes the tables of the database.
	 */
	public function optimize(): void;
}
