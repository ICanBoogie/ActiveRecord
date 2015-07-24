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

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;

/**
 * Connection driver for SQLite.
 */
class SQLiteDriver extends MySQLDriver
{
	/**
	 * @inheritdoc
	 */
	protected function render_create_table($unprefixed_table_name, Schema $schema)
	{
		$quoted_table_name = $this->resolve_quoted_table_name($unprefixed_table_name);
		$lines = $this->render_create_table_lines($schema);
		$lines[] = $this->render_create_table_primary_key($schema);

		return "CREATE TABLE $quoted_table_name\n(\n\t" . implode(",\n\t", array_filter($lines)) . "\n)";
	}

	/**
	 * Overrides column rendering of integer primary key.
	 *
	 * @inheritdoc
	 */
	protected function render_create_table_line(Schema $schema, $column_id, $column)
	{
		if ($column->primary && $column->type == SchemaColumn::TYPE_INTEGER)
		{
			$quoted_column_id = $this->quote_identifier($column_id);

			return "$quoted_column_id INTEGER NOT NULL";
		}

		return parent::render_create_table_line($schema, $column_id, $column);
	}

	/**
	 * @inheritdoc
	 */
	public function table_exists($unprefixed_name)
	{
		$name = $this->resolve_table_name($unprefixed_name);

		$tables = $this->connection
			->query('SELECT name FROM sqlite_master WHERE type = "table" AND name = ?', [ $name ])
			->all(\PDO::FETCH_COLUMN);

		return !!$tables;
	}

	/**
	 * @inheritdoc
	 */
	public function optimize()
	{
		$this->connection->exec('VACUUM');
	}
}
