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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\OffsetNotDefined;
use IteratorAggregate;

/**
 * Representation of a database table schema.
 *
 * @property-read SchemaColumn[] $columns The columns of the schema.
 * @property-read array $indexes The indexes of the schema.
 * @property-read array $unique_indexes The unique indexes of the schema.
 * @property-read array|string|null $primary The primary key of the schema. A multi-dimensional
 * primary key is returned as an array.
 *
 * @implements IteratorAggregate<string, SchemaColumn>
 */
class Schema implements \ArrayAccess, IteratorAggregate
{
	/*
	 * @uses get_columns
	 * @uses get_primary
	 * @uses get_indexes
	 * @uses get_unique_indexes
	 */
	use AccessorTrait;

	/**
	 * @var array<string, SchemaColumn>
	 */
	private $columns = [];

	/**
	 * @return array<string, SchemaColumn>
	 */
	private function get_columns(): array
	{
		return $this->columns;
	}

	private function get_primary()
	{
		$primary = [];

		foreach ($this->columns as $column_id => $column)
		{
			if (!$column->primary)
			{
				continue;
			}

			$primary[] = $column_id;
		}

		switch (\count($primary))
		{
			case 0: return null;
			case 1: return \reset($primary);
			default: return $primary;
		}
	}

	private function get_indexes(): array
	{
		return $this->collect_indexes_by_type('indexed');
	}

	private function get_unique_indexes(): array
	{
		return $this->collect_indexes_by_type('unique');
	}

	/**
	 * @param array<string, array> $options
	 */
	public function __construct(array $options)
	{
		foreach ($options as $column_id => $column_options)
		{
			$this[$column_id] = $column_options;
		}
	}

	/**
	 * Checks if a column exists.
	 *
	 * @param string $column_id Column identifier.
	 *
	 * @return bool
	 */
	public function offsetExists($column_id)
	{
		return isset($this->columns[$column_id]);
	}

	/**
	 * Returns a column.
	 *
	 * @param string $column_id
	 *
	 * @return SchemaColumn
	 *
	 * @throws OffsetNotDefined if the column is not defined.
	 */
	public function offsetGet($column_id)
	{
		if (!$this->offsetExists($column_id))
		{
			throw new OffsetNotDefined([ $column_id, $this ]);
		}

		return $this->columns[$column_id];
	}

	/**
	 * Adds a column to the schema.
	 *
	 * @param string $column_id
	 * @param string|array|SchemaColumn $column_options
	 */
	public function offsetSet($column_id, $column_options)
	{
		if (\is_string($column_options))
		{
			$column_options = [ $column_options ];
		}

		if (!$column_options instanceof SchemaColumn)
		{
			$column_options = new SchemaColumn($column_options);
		}

		$this->columns[$column_id] = $column_options;
	}

	/**
	 * Removes a column from the schema.
	 *
	 * @param string $column_id
	 */
	public function offsetUnset($column_id)
	{
		unset($this->columns[$column_id]);
	}

	/**
	 * Returns columns iterator.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->columns);
	}

	/**
	 * Collect index name by type.
	 *
	 * @param string $type One of [ "indexed, "unique" ].
	 *
	 * @return array
	 */
	private function collect_indexes_by_type(string $type): array
	{
		$indexes = [];

		foreach ($this->columns as $column_id => $column)
		{
			$name = $column->$type;

			if (!$name)
			{
				continue;
			}

			$indexes[ $name === true ? $column_id : $name ][] = $column_id;
		}

		return $indexes;
	}

    /**
	 * Filters values according to the schema columns.
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	public function filter(array $values): array
	{
		return \array_intersect_key($values, $this->columns);
	}
}
