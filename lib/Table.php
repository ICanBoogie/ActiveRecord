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

use ICanBoogie\DateTime;
use ICanBoogie\Object;

/**
 * A representation of a database table.
 *
 * @property-read Connection $connection Connection used by the table.
 * @property-read Schema $schema The schema of the table.
 * @property-read array $schema_options The options used to create the {@link Schema} instance.
 * @property-read Schema $extended_schema The extended schema of the table.
 * @property-read string $name Name of the table, which might include a prefix.
 * @property-read string $unprefixed_name Unprefixed name of the table.
 * @property-read string|array|null $primary Primary key of the table, or `null` if there is none.
 * @property-read string $alias The alias name of the table.
 * @property-read Table|null $parent The parent of the table.
 */
class Table extends Object
{
	/**
	 * Alias of the table.
	 *
	 * @var string
	 */
	const ALIAS = 'alias';

	/**
	 * Connection.
	 *
	 * @var string
	 */
	const CONNECTION = 'connection';

	/**
	 * Extended model.
	 *
	 * @var string
	 */
	const EXTENDING = 'extends';
	const IMPLEMENTING = 'implements';

	/**
	 * Unprefixed Name of the table.
	 *
	 * @var string
	 */
	const NAME = 'name';

	/**
	 * Schema of the table.
	 *
	 * @var string
	 */
	const SCHEMA = 'schema';

	/**
	 * A database connection.
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Returns the connection used by the table.
	 *
	 * @return Connection
	 */
	protected function get_connection()
	{
		return $this->connection;
	}

	/**
	 * Name of the table, including the prefix defined by the model's connection.
	 *
	 * @var string
	 */
	protected $name;

	protected function get_name()
	{
		return $this->name;
	}

	/**
	 * The unprefixed name of the table.
	 *
	 * @return string
	 */
	protected $unprefixed_name;

	protected function get_unprefixed_name()
	{
		return $this->unprefixed_name;
	}

	/**
	 * Primary key of the table, retrieved from the schema defined using the {@link SCHEMA} attribute.
	 *
	 * @var mixed
	 */
	protected $primary;

	protected function get_primary()
	{
		return $this->primary;
	}

	/**
	 * Alias for the table's name, which can be defined using the {@link ALIAS} attribute
	 * or automatically created.
	 *
	 * The "{primary}" placeholder used in queries is replaced by the properties value.
	 *
	 * @var string
	 */
	protected $alias;

	protected function get_alias()
	{
		return $this->alias;
	}

	/**
	 * Schema for the table.
	 *
	 * @var Schema
	 */
	protected $schema;

	protected function get_schema()
	{
		return $this->schema;
	}

	/**
	 * Schema options provided using {@link SCHEMA} during construct.
	 *
	 * @var array
	 */
	protected $schema_options;

	protected function get_schema_options()
	{
		return $this->schema_options;
	}

	/**
	 * The parent is used when the table is in a hierarchy, which is the case if the table
	 * extends another table.
	 *
	 * @var Table
	 */
	protected $parent;

	protected function get_parent()
	{
		return $this->parent;
	}

	protected $implements = [];

	/**
	 * SQL fragment for the FROM clause of the query, made of the table's name and alias and those
	 * of the hierarchy.
	 *
	 * @var string
	 */
	protected $update_join;

	/**
	 * SQL fragment for the FROM clause of the query, made of the table's name and alias and those
	 * of the related tables, inherited and implemented.
	 *
	 * The "{self_and_related}" placeholder used in queries is replaced by the properties value.
	 *
	 * @var string
	 */
	protected $select_join;

	/**
	 * Initializes the following properties: {@link $alias}, {@link $connection},
	 * {@link implements}, {@link $unprefixed_name}, {@link $schema} and {@link $parent}.
	 *
	 * @param array $attributes
	 *
	 * @throws \InvalidArgumentException if the {@link CONNECTION} attribute is empty.
	 */
	public function __construct(array $attributes)
	{
		foreach ($attributes as $attribute => $value)
		{
			switch ($attribute)
			{
				case self::ALIAS: $this->alias = $value; break;
				case self::CONNECTION: $this->connection = $value; break;
				case self::IMPLEMENTING: $this->implements = $value; break;
				case self::NAME: $this->unprefixed_name = $value; break;
				case self::SCHEMA: $this->schema_options = $value; break;
				case self::EXTENDING: $this->parent = $value; break;
			}
		}

		if (!$this->unprefixed_name)
		{
			throw new \InvalidArgumentException('The <code>NAME</code> attribute is empty.');
		}

		if (preg_match('#([^0-9,a-z,A-Z$_])#', $this->unprefixed_name, $matches))
		{
			throw new \InvalidArgumentException("Invalid character in table name \"$this->unprefixed_name\": {$matches[0]}.");
		}

		if (!$this->schema_options)
		{
			throw new \InvalidArgumentException('The <code>SCHEMA</code> attribute is empty.');
		}

		if (empty($this->schema_options['fields']))
		{
			throw new \InvalidArgumentException("Schema fields are empty for table \"{$this->unprefixed_name}\".");
		}

		if ($this->parent && !($this->parent instanceof self))
		{
			throw new \InvalidArgumentException("EXTENDING must be an instance of " . __CLASS__ . ". Given: {$this->parent}.");
		}

		#
		# alias
		#

		if (!$this->alias)
		{
			$alias = $this->unprefixed_name;

			$pos = strrpos($alias, '_');

			if ($pos !== false)
			{
				$alias = substr($alias, $pos + 1);
			}

			$alias = \ICanBoogie\singularize($alias);

			$this->alias = $alias;
		}

		#
		# if we have a parent, we need to extend our fields with our parent primary key
		#

		$parent = $this->parent;

		if ($parent)
		{
			$this->connection = $parent->connection;

			$primary = $parent->primary;
			$primary_definition = $parent->schema[$primary];
			$primary_definition->auto_increment = false;

			$this->schema_options['fields'] = [ $primary => $primary_definition ] + $this->schema_options['fields'];

			#
			# implements are inherited too
			#

			if ($parent->implements)
			{
				$this->implements = array_merge($parent->implements, $this->implements);
			}
		}

		$connection = $this->connection;

		if (!($connection instanceof Connection))
		{
			throw new \InvalidArgumentException
			(
				'<code>connection</code> must be an instance of Connection, given: '
				. (is_object($connection) ? 'instance of ' . get_class($connection) : gettype($connection))
				. '.'
			);
		}

		$this->name = $connection->table_name_prefix . $this->unprefixed_name;

		#
		# Create a Schema instance from the schema options and retrieve the primary key, if any.
		#

		$this->schema = new Schema($this->schema_options);

		if ($this->schema->primary)
		{
			$this->primary = $this->schema->primary;
		}

		#
		# resolve inheritance and create a lovely _inner join_ string
		#

		$join = '';

		$parent = $this->parent;

		while ($parent)
		{
			$join .= " INNER JOIN `{$parent->name}` `{$parent->alias}` USING(`{$this->primary}`)";

			$parent = $parent->parent;
		}

		$this->update_join = $join;

		$join = "`{$this->alias}`" . $join;

		#
		# resolve implements
		#

		if ($this->implements)
		{
			if (!is_array($this->implements))
			{
				throw new \InvalidArgumentException('<code>IMPLEMENTING</code> must be an array.');
			}

			$i = 1;

			foreach ($this->implements as $implement)
			{
				if (!is_array($implement))
				{
					throw new \InvalidArgumentException('<code>IMPLEMENTING</code> must be an array.');
				}

				$table = $implement['table'];

				if (!($table instanceof Table))
				{
					throw new \InvalidArgumentException(sprintf('Implements table must be an instance of ICanBoogie\ActiveRecord\Table: %s given.', get_class($table)));
				}

				$name = $table->name;
				$primary = $table->primary;

				$join .= empty($implement['loose']) ? 'INNER' : 'LEFT';
				$join .= " JOIN `$name` AS {$table->alias} USING(`$primary`)";

				$i++;
			}
		}

		$this->select_join = $join;
	}

	/**
	 * Alias to {@link query()}.
	 *
	 * @param string $query
	 * @param array $args
	 * @param array $options
	 *
	 * @return Statement
	 */
	public function __invoke($query, array $args = [], array $options = [])
	{
		return $this->query($query, $args, $options);
	}

	/*
	**

	INSTALL

	**
	*/

	public function install()
	{
		if (!$this->schema)
		{
			throw new \Exception("Missing schema to install table {$this->unprefixed_name}.");
		}

		return $this->connection->create_table($this->unprefixed_name, $this->schema);
	}

	public function uninstall()
	{
		return $this->drop();
	}

	/**
	 * Checks whether the table is installed.
	 *
	 * @return bool `true` if the table exists, `false` otherwise.
	 */
	public function is_installed()
	{
		return $this->connection->table_exists($this->unprefixed_name);
	}

	/**
	 * Returns the extended schema.
	 *
	 * @return Schema
	 */
	protected function lazy_get_extended_schema()
	{
		$table = $this;
		$options = [];

		while ($table)
		{
			$options[] = $table->schema_options;

			$table = $table->parent;
		}

		$options = array_reverse($options);
		$options = call_user_func_array('\ICanBoogie\array_merge_recursive', $options);

		return new Schema($options);
	}

	/**
	 * Resolve statement placeholders.
	 *
	 * The following placeholder are replaced:
	 *
	 * - `{alias}`: The alias of the table.
	 * - `{prefix}`: The prefix used for the tables of the connection.
	 * - `{primary}`: The primary key of the table.
	 * - `{self}`: The name of the table.
	 * - `{self_and_related}`: The escaped name of the table and the possible JOIN clauses.
	 *
	 * Note: If the table has a multi-column primary keys `{primary}` is replaced by
	 * `__multicolumn_primary__<concatened_columns>` where `<concatened_columns>` is a the columns
	 * concatenated with an underscore ("_") as separator. For instance, if a table primary key is
	 * made of columns "p1" and "p2", `{primary}` is replaced by `__multicolumn_primary__p1_p2`.
	 * It's not very helpful, but we still have to decide what to do with this.
	 *
	 * @param string $statement The statement to resolve.
	 *
	 * @return string
	 */
	public function resolve_statement($statement)
	{
		$primary = $this->primary;
		$primary = is_array($primary) ? '__multicolumn_primary__' . implode('_', $primary) : $primary;

		return strtr($statement, [

			'{alias}' => $this->alias,
			'{prefix}' => $this->connection->table_name_prefix,
			'{primary}' => $primary,
			'{self}' => $this->name,
			'{self_and_related}' => "`$this->name`" . ($this->select_join ? " $this->select_join" : '')
		]);
	}

	/**
	 * Interface to the connection's prepare method.
	 *
	 * The statement is resolved by the {@link resolve_statement()} method before the call is
	 * forwarded.
	 *
	 * @inheritdoc
	 *
	 * @return Statement
	 */
	public function prepare($query, $options = [])
	{
		$query = $this->resolve_statement($query);

		return $this->connection->prepare($query, $options);
	}

	public function quote($string, $parameter_type=\PDO::PARAM_STR)
	{
		return $this->connection->quote($string, $parameter_type);
	}

	/**
	 * Executes a statement.
	 *
	 * The statement is prepared by the {@link prepare()} method before it is executed.
	 *
	 * @inheritdoc
	 *
	 * @return mixed
	 */
	public function execute($query, array $args = [], array $options = [])
	{
		$statement = $this->prepare($query, $options);

		return $statement->execute($args);
	}

	/**
	 * Interface to the connection's query() method.
	 *
	 * The statement is resolved using the resolve_statement() method and prepared.
	 *
	 * @param string $query
	 * @param array $args
	 * @param array $options
	 *
	 * @return Statement
	 */
	public function query($query, array $args = [], array $options = [])
	{
		$query = $this->resolve_statement($query);

		$statement = $this->prepare($query, $options);
		$statement->execute($args);

		return $statement;
	}

	protected function filter_values(array $values, $extended = false)
	{
		$filtered = [];
		$holders = [];
		$identifiers = [];

		$schema = $extended ? $this->extended_schema : $this->schema;

		foreach ($values as $identifier => $value)
		{
			if (!isset($schema[$identifier]))
			{
				continue;
			}

			if ($value instanceof \DateTime)
			{
				$value = DateTime::from($value);
				$value = $value->utc->as_db;
			}

			$filtered[] = $value;
			$holders[$identifier] = '`' . $identifier . '` = ?';
			$identifiers[] = '`' . $identifier . '`';
		}

		return [ $filtered, $holders, $identifiers ];
	}

	public function save(array $values, $id = null, array $options = [])
	{
		if ($id)
		{
			return $this->update($values, $id) ? $id : false;
		}

		return $this->save_callback($values, $id, $options);
	}

	protected function save_callback(array $values, $id = null, array $options = [])
	{
		if ($id)
		{
			$this->update($values, $id);

			return $id;
		}

		$parent_id = 0;

		if ($this->parent)
		{
			$parent_id = $this->parent->save_callback($values, $id, $options);

			if (!$parent_id)
			{
				throw new \Exception("Parent save failed: {$this->parent->name} returning {$parent_id}.");
			}
		}

		$driver_name = $this->connection->driver_name;

		list($filtered, $holders, $identifiers) = $this->filter_values($values);

		// FIXME: ALL THIS NEED REWRITE !

		if ($holders)
		{
			// faire attention à l'id, si l'on revient du parent qui a inséré, on doit insérer aussi, avec son id

			if ($id)
			{
				$filtered[] = $id;

				$statement = 'UPDATE `{self}` SET ' . implode(', ', $holders) . ' WHERE `{primary}` = ?';

				$statement = $this->prepare($statement);

				$rc = $statement->execute($filtered);
			}
			else
			{
				if ($driver_name == 'mysql')
				{
					if ($parent_id && empty($holders[$this->primary]))
					{
						$filtered[] = $parent_id;
						$holders[] = '`{primary}` = ?';
					}

					$statement = 'INSERT INTO `{self}` SET ' . implode(', ', $holders);

					$statement = $this->prepare($statement);

					$rc = $statement->execute($filtered);
				}
				else if ($driver_name == 'sqlite')
				{
					$rc = $this->insert($values, $options);
				}
			}
		}
		else if ($parent_id && !$id)
		{
			#
			# a new entry has been created, but we don't have any other fields then the primary key
			#

			if (empty($identifiers[$this->primary]))
			{
				$identifiers[] = '`{primary}`';
				$filtered[] = $parent_id;
			}

			$identifiers = implode(', ', $identifiers);
			$placeholders = implode(', ', array_fill(0, count($filtered), '?'));

			$statement = "INSERT INTO `{self}` ($identifiers) VALUES ($placeholders)";
			$statement = $this->prepare($statement);

			$rc = $statement->execute($filtered);
		}
		else
		{
			$rc = true;
		}

		if ($parent_id)
		{
			return $parent_id;
		}

		if (!$rc)
		{
			return false;
		}

		if (!$id)
		{
			$id = $this->connection->lastInsertId();
		}

		return $id;
	}

	/**
	 * Inserts values into the table.
	 *
	 * @param array $values The values to insert.
	 * @param array $options The following options can be used:
	 * - `ignore`: Ignore duplicate errors.
	 * - `on duplicate`: specifies the column to update on duplicate, and the values to update
	 * them. If `true` the `$values` array is used, after the primary keys has been removed.
	 *
	 * @return mixed
	 */
	public function insert(array $values, array $options = [])
	{
		list($values, $holders, $identifiers) = $this->filter_values($values);

		if (!$values)
		{
			return null;
		}

		$driver_name = $this->connection->driver_name;

		$on_duplicate = isset($options['on duplicate']) ? $options['on duplicate'] : null;

		if ($driver_name == 'mysql')
		{
			$query = 'INSERT';

			if (!empty($options['ignore']))
			{
				$query .= ' IGNORE ';
			}

			$query .= ' INTO `{self}` SET ' . implode(', ', $holders);

			if ($on_duplicate)
			{
				if ($on_duplicate === true)
				{
					#
					# if 'on duplicate' is true, we use the same input values, but we take care of
					# removing the primary key and its corresponding value
					#

					$update_values = array_combine(array_keys($holders), $values);
					$update_holders = $holders;

					$primary = $this->primary;

					if (is_array($primary))
					{
						$flip = array_flip($primary);

						$update_holders = array_diff_key($update_holders, $flip);
						$update_values = array_diff_key($update_values, $flip);
					}
					else
					{
						unset($update_holders[$primary]);
						unset($update_values[$primary]);
					}

					$update_values = array_values($update_values);
				}
				else
				{
					list($update_values, $update_holders) = $this->filter_values($on_duplicate);
				}

				$query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $update_holders);

				$values = array_merge($values, $update_values);
			}
		}
		else if ($driver_name == 'sqlite')
		{
			$holders = array_fill(0, count($identifiers), '?');

			$query = 'INSERT' . ($on_duplicate ? ' OR REPLACE' : '') . ' INTO `{self}` (' . implode(', ', $identifiers) . ') VALUES (' . implode(', ', $holders) . ')';
		}
		else
		{
			throw new \LogicException("Unsupported drive: $driver_name.");
		}

		return $this->execute($query, $values);
	}

	/**
	 * Update the values of an entry.
	 *
	 * Even if the entry is spread over multiple tables, all the tables are updated in a single
	 * step.
	 *
	 * @param array $values
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function update(array $values, $key)
	{
		#
		# SQLite doesn't support UPDATE with INNER JOIN.
		#

		if ($this->connection->driver_name == 'sqlite')
		{
			$table = $this;
			$rc = true;

			while ($table)
			{
				list($table_values, $holders) = $table->filter_values($values);

				if ($holders)
				{
					$query = 'UPDATE `{self}` SET ' . implode(', ', $holders) . ' WHERE `{primary}` = ?';
					$table_values[] = $key;

					$rc = $table->execute($query, $table_values);

					if (!$rc)
					{
						return $rc;
					}
				}

				$table = $table->parent;
			}

			return $rc;
		}

		list($values, $holders) = $this->filter_values($values, true);

		$query = 'UPDATE `{self}` ' . $this->update_join . ' SET ' . implode(', ', $holders) . ' WHERE `{primary}` = ?';
		$values[] = $key;

		return $this->execute($query, $values);
	}

	/**
	 * Deletes a record.
	 *
	 * @param mixed $key Identifier of the record.
	 *
	 * @return bool
	 */
	public function delete($key)
	{
		if ($this->parent)
		{
			$this->parent->delete($key);
		}

		$where = 'where ';

		if (is_array($this->primary))
		{
			$parts = [];

			foreach ($this->primary as $identifier)
			{
				$parts[] = '`' . $identifier . '` = ?';
			}

			$where .= implode(' and ', $parts);
		}
		else
		{
			$where .= '`{primary}` = ?';
		}

		$statement = $this->prepare('delete from `{self}` ' . $where);
		$statement((array) $key);

		return !!$statement->rowCount();
	}

	// FIXME-20081223: what about extends ?

	public function truncate()
	{
		if ($this->connection->driver_name == 'sqlite')
		{
			$rc = $this->execute('delete from {self}');

			$this->execute('vacuum');

			return $rc;
		}

		return $this->execute('truncate table `{self}`');
	}

	public function drop(array $options=[])
	{
		$query = 'DROP TABLE ';

		if (!empty($options['if exists']))
		{
			$query .= 'if exists ';
		}

		$query .= '`{self}`';

		return $this->execute($query);
	}
}
