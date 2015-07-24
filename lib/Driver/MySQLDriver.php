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

use ICanBoogie\ActiveRecord\Driver;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;

/**
 * Connection driver for MySQL.
 */
class MySQLDriver extends BasicDriver
{
	/**
	 * @inheritdoc
	 */
	public function create_table($unprefixed_table_name, Schema $schema)
	{
		$statement = $this->render_create_table($unprefixed_table_name, $schema);
		$this->connection->exec($statement);

		$this->create_indexes($unprefixed_table_name, $schema);
		$this->create_unique_indexes($unprefixed_table_name, $schema);
	}

	/**
	 * @inheritdoc
	 */
	public function create_indexes($unprefixed_table_name, Schema $schema)
	{
		$this->create_indexes_of('', $unprefixed_table_name, $schema->indexes);
	}

	/**
	 * @inheritdoc
	 */
	public function create_unique_indexes($unprefixed_table_name, Schema $schema)
	{
		$this->create_indexes_of('UNIQUE', $unprefixed_table_name, $schema->unique_indexes);
	}

	/**
	 * @inheritdoc
	 */
	public function table_exists($unprefixed_name)
	{
		$tables = $this->connection->query('SHOW TABLES')->all(\PDO::FETCH_COLUMN);

		return in_array($this->resolve_table_name($unprefixed_name), $tables);
	}

	/**
	 * @inheritdoc
	 */
	public function optimize()
	{
		$connection = $this->connection;
		$tables = $connection->query('SHOW TABLES')->all(\PDO::FETCH_COLUMN);
		$connection->exec('OPTIMIZE TABLE ' . implode(', ', $tables));
	}

	/**
	 * Renders _create table_ statement.
	 *
	 * @param string $unprefixed_table_name
	 * @param Schema $schema
	 *
	 * @return string
	 */
	protected function render_create_table($unprefixed_table_name, Schema $schema)
	{
		$connection = $this->connection;
		$quoted_table_name = $this->resolve_quoted_table_name($unprefixed_table_name);
		$lines = $this->render_create_table_lines($schema);
		$lines[] = $this->render_create_table_primary_key($schema);

		return "CREATE TABLE $quoted_table_name\n(\n\t" . implode(",\n\t", array_filter($lines)) . "\n)"
		. " CHARACTER SET $connection->charset  COLLATE $connection->collate";
	}

	/**
	 * Renders the lines used to create a table.
	 *
	 * @param Schema $schema
	 *
	 * @return array
	 */
	protected function render_create_table_lines(Schema $schema)
	{
		$lines = [];

		foreach ($schema as $column_id => $column)
		{
			$lines[$column_id] = $this->render_create_table_line($schema, $column_id, $column);
		}

		return $lines;
	}

	/**
	 * Renders a line used to create a table.
	 *
	 * @param Schema $schema
	 * @param string $column_id
	 * @param SchemaColumn $column
	 *
	 * @return string
	 */
	protected function render_create_table_line(Schema $schema, $column_id, $column)
	{
		$quoted_column_id = $this->quote_identifier($column_id);

		return "$quoted_column_id $column";
	}

	/**
	 * Renders primary key clause to create table.
	 *
	 * @param Schema $schema
	 *
	 * @return string
	 */
	protected function render_create_table_primary_key(Schema $schema)
	{
		$primary = $schema->primary;

		if (!$primary)
		{
			return '';
		}

		$quoted_primary_key = $this->quote_identifier($primary);

		if (is_array($quoted_primary_key))
		{
			$quoted_primary_key = implode(', ', $quoted_primary_key);
		}

		return "PRIMARY KEY($quoted_primary_key)";
	}

	/**
	 * Creates indexes of a give type.
	 *
	 * @param string $type e.g. "UNIQUE".
	 * @param string $unprefixed_table_name
	 * @param array $indexes
	 */
	protected function create_indexes_of($type, $unprefixed_table_name, array $indexes)
	{
		if (!$indexes)
		{
			return;
		}

		$connection = $this->connection;
		$quoted_table_name = $this->resolve_quoted_table_name($unprefixed_table_name);

		if ($type)
		{
			$type .= ' ';
		}

		foreach ($indexes as $index_id => $column_names)
		{
			$column_names = $this->quote_identifier($column_names);
			$rendered_column_names = implode(', ', $column_names);
			$quoted_index_id = $this->quote_identifier($index_id);

			$connection->exec("CREATE {$type}INDEX $quoted_index_id ON $quoted_table_name ($rendered_column_names)");
		}
	}
}
