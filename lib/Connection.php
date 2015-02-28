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
use ICanBoogie\ActiveRecord\ConnectionOptions as Options;

/**
 * A connection to a database.
 *
 * @property-read string $charset The character set used to communicate with the database. Defaults to "utf8".
 * @property-read string $collate The collation of the character set. Defaults to "utf8_general_ci".
 * @property-read string $driver_name Name of the PDO driver.
 * @property-read string $id Identifier of the database connection.
 * @property-read string $table_name_prefix The prefix to prepend to every table name.
 */
class Connection extends \PDO
{
	use AccessorTrait;

	/**
	 * Connection identifier.
	 *
	 * @var string
	 */
	private $id;

	protected function get_id()
	{
		return $this->id;
	}

	/**
	 * Prefix to prepend to every table name.
	 *
	 * If set to "dev", all table names will be named like "dev_nodes", "dev_contents", etc.
	 * This is a convenient way of creating a namespace for tables in a shared database.
	 * By default, the prefix is the empty string, that is there is not prefix.
	 *
	 * @var string
	 */
	private $table_name_prefix = Options::DEFAULT_TIMEZONE;

	protected function get_table_name_prefix()
	{
		return $this->table_name_prefix;
	}

	/**
	 * Charset for the connection. Also used to specify the charset while creating tables.
	 *
	 * @var string
	 */
	private $charset = Options::DEFAULT_CHARSET;

	protected function get_charset()
	{
		return $this->charset;
	}

	/**
	 * Used to specify the collate while creating tables.
	 *
	 * @var string
	 */
	private $collate = Options::DEFAULT_COLLATE;

	protected function get_collate()
	{
		return $this->collate;
	}

	/**
	 * Timezone of the connection.
	 *
	 * @var string
	 */
	private $timezone = Options::DEFAULT_TIMEZONE;

	protected function get_timezone()
	{
		return $this->timezone;
	}

	/**
	 * Driver name for the connection.
	 *
	 * @var string
	 */
	private $driver_name;

	protected function get_driver_name()
	{
		return $this->driver_name;
	}

	/**
	 * The number of database queries and executions, used for statistics purpose.
	 *
	 * @var int
	 */
	public $queries_count = 0;

	/**
	 * The number of micro seconds spent per request.
	 *
	 * @var array[]array
	 */
	public $profiling = [];

	/**
	 * Establish a connection to a database.
	 *
	 * Custom options can be specified using the driver-specific connection options. See
	 * {@link Options}.
	 *
	 * @link http://www.php.net/manual/en/pdo.construct.php
	 * @link http://dev.mysql.com/doc/refman/5.5/en/time-zone-support.html
	 *
	 * @param string $dsn
	 * @param string $username
	 * @param string $password
	 * @param array $options
	 */
	public function __construct($dsn, $username = null, $password = null, $options = [])
	{
		$this->driver_name = $this->resolve_driver_name($dsn);
		$this->apply_options($options);
		$this->before_connection($options);

		parent::__construct($dsn, $username, $password, $options);

		$this->after_connection();
	}

	/**
	 * Alias to {@link query}.
	 *
	 * @return Statement
	 */
	public function __invoke()
	{
		return call_user_func_array([ $this, 'query' ], func_get_args());
	}

	/**
	 * Resolve the driver name from the DSN string.
	 *
	 * @param string $dsn
	 *
	 * @return string
	 */
	protected function resolve_driver_name($dsn)
	{
		return explode(':', $dsn, 2)[0];
	}

	/**
	 * Applies options to the instance.
	 *
	 * @param array $options
	 */
	protected function apply_options(array $options)
	{
		$options = Options::normalize($options);

		$this->id = $options[Options::ID];
		$this->table_name_prefix = $options[Options::TABLE_NAME_PREFIX];

		if ($this->table_name_prefix)
		{
			$this->table_name_prefix .= '_';
		}

		list($this->charset, $this->collate) = extract_charset_and_collate($options[Options::CHARSET_AND_COLLATE]);

		$this->timezone = $options[Options::TIMEZONE];
	}

	/**
	 * Called before the connection.
	 *
	 * May alter the options according to the driver.
	 *
	 * @param array $options
	 */
	protected function before_connection(array &$options)
	{
		if ($this->driver_name != 'mysql')
		{
			return;
		}

		$init_command = 'SET NAMES ' . $this->charset;

		if ($this->timezone)
		{
			$init_command .= ', time_zone = "' . $this->timezone . '"';
		}

		$options += [

			self::MYSQL_ATTR_INIT_COMMAND => $init_command

		];
	}

	protected function after_connection()
	{
		$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
		$this->setAttribute(self::ATTR_STATEMENT_CLASS, [ Statement::class ]);
	}

	/**
	 * Overrides the method to resolve the statement before it is prepared, then set its fetch
	 * mode and connection.
	 *
	 * @param string $statement Query statement.
	 * @param array $options
	 *
	 * @return Statement The prepared statement.
	 *
	 * @throws StatementNotValid if the statement cannot be prepared.
	 */
	public function prepare($statement, $options = [])
	{
		$statement = $this->resolve_statement($statement);

		try
		{
			/* @var $statement Statement */
			$statement = parent::prepare($statement, $options);
		}
		catch (\PDOException $e)
		{
			throw new StatementNotValid($statement, 500, $e);
		}

		$statement->connection = $this;

		if (isset($options['mode']))
		{
			$mode = (array) $options['mode'];

			call_user_func_array([ $statement, 'setFetchMode' ], $mode);
		}

		return $statement;
	}

	/**
	 * Overrides the method in order to prepare (and resolve) the statement and execute it with
	 * the specified arguments and options.
	 *
	 * @inheritdoc
	 *
	 * @return Statement
	 */
	public function query($statement, array $args = [], array $options = [])
	{
		$statement = $this->prepare($statement, $options);
		$statement->execute($args);

		return $statement;
	}

	/**
	 * Executes a statement.
	 *
	 * The statement is resolved using the {@link resolve_statement()} method before it is
	 * executed.
	 *
	 * The execution of the statement is wrapped in a try/catch block. {@link PDOException} are
	 * caught and {@link StatementNotValid} exception are thrown with additional information
	 * instead.
	 *
	 * Using this method increments the `queries_by_connection` stat.
	 *
	 * @inheritdoc
	 *
	 * @throws StatementNotValid if the statement cannot be executed.
	 */
	public function exec($statement)
	{
		$statement = $this->resolve_statement($statement);

		try
		{
			$this->queries_count++;

			return parent::exec($statement);
		}
		catch (\PDOException $e)
		{
			throw new StatementNotValid($statement, 500, $e);
		}
	}

	/**
	 * Places quotes around the identifier.
	 *
	 * @param string|array $identifier
	 *
	 * @return string|array
	 */
	public function quote_identifier($identifier)
	{
		$quote = $this->driver_name == 'oci' ? '"' : '`';

		if (is_array($identifier))
		{
			return array_map(function($v) use ($quote) {

				return $quote . $v . $quote;

			}, $identifier);
		}

		return $quote . $identifier . $quote;
	}

	/**
	 * Replaces placeholders with their value.
	 *
	 * The following placeholders are supported:
	 *
	 * - `{prefix}`: replaced by the {@link $table_name_prefix} property.
	 * - `{charset}`: replaced by the {@link $charset} property.
	 * - `{collate}`: replaced by the {@link $collate} property.
	 *
	 * @param string $statement
	 *
	 * @return string The resolved statement.
	 */
	public function resolve_statement($statement)
	{
		return strtr($statement, [

			'{prefix}' => $this->table_name_prefix,
			'{charset}' => $this->charset,
			'{collate}' => $this->collate

		]);
	}

	/**
	 * Alias for the `beginTransaction()` method.
	 *
	 * @see PDO::beginTransaction()
	 */
	public function begin()
	{
		return $this->beginTransaction();
	}

	/**
	 * Parses a schema to create a schema with low level definitions.
	 *
	 * For example, a column defined as 'serial' is parsed as :
	 *
	 * 'type' => 'integer', 'serial' => true, 'size' => 'big', 'unsigned' => true,
	 * 'primary' => true
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public function parse_schema(array $schema)
	{
		$driver_name = $this->driver_name;

		$schema['primary'] = [];
		$schema['indexes'] = [];

		foreach ($schema['fields'] as $identifier => &$definition)
		{
			$definition = (array) $definition;

			#
			# translate special indexes to keys
			#

			if (isset($definition[0]))
			{
				$definition['type'] = $definition[0];

				unset($definition[0]);
			}

			if (isset($definition[1]))
			{
				$definition['size'] = $definition[1];

				unset($definition[1]);
			}

			#
			# handle special types
			#

			switch($definition['type'])
			{
				case 'serial':
				{
					$definition['type'] = 'integer';

					#
					# because auto increment only works on "INTEGER AUTO INCREMENT" in SQLite
					#

					if ($driver_name != 'sqlite')
					{
						$definition += [ 'size' => 'big', 'unsigned' => true ];
					}

					$definition += [ 'auto increment' => true, 'primary' => true ];
				}
				break;

				case 'foreign':
				{
					$definition['type'] = 'integer';

					if ($driver_name != 'sqlite')
					{
						$definition += [ 'size' => 'big', 'unsigned' => true ];
					}

					$definition += [ 'indexed' => true ];
				}
				break;

				case 'varchar':
				{
					$definition += [ 'size' => 255 ];
				}
				break;
			}

			#
			# primary
			#

			if (isset($definition['primary']) && !in_array($identifier, $schema['primary']))
			{
				$schema['primary'][] = $identifier;
			}

			#
			# indexed
			#

			if (!empty($definition['indexed']) && empty($definition['unique']))
			{
				$index = $definition['indexed'];

				if (is_string($index))
				{
					if (isset($schema['indexes'][$index]) && in_array($identifier, $schema['indexes'][$index]))
					{
						# $identifier is already defined in $index
					}
					else
					{
						$schema['indexes'][$index][] = $identifier;
					}
				}
				else
				{
					if (!in_array($identifier, $schema['indexes']))
					{
						$schema['indexes'][$identifier] = $identifier;
					}
				}
			}
		}

		#
		# indexes that are part of the primary key are deleted
		#

		if ($schema['indexes'] && $schema['primary'])
		{
			$schema['indexes'] = array_diff($schema['indexes'], $schema['primary']);
		}

		if (count($schema['primary']) == 1)
		{
			$schema['primary'] = $schema['primary'][0];
		}

		return $schema;
	}

	/**
	 * Creates a table of the specified name and schema.
	 *
	 * @param string $unprefixed_name The unprefixed name of the table.
	 * @param array $schema The schema of the table.
	 *
	 * @return bool
	 */
	public function create_table($unprefixed_name, array $schema)
	{
		// FIXME-20091201: I don't think 'UNIQUE' is properly implemented

		$driver_name = $this->driver_name;
		$unique_list = [];

		$schema = $this->parse_schema($schema);

		$parts = [];

		foreach ($schema['fields'] as $identifier => $params)
		{
			$definition = '`' . $identifier . '`';

			$type = $params['type'];
			$size = isset($params['size']) ? $params['size'] : 0;

			switch ($type)
			{
				case 'blob':
				case 'char':
				case 'integer':
				case 'text':
				case 'varchar':
				case 'bit':
				{
					if ($size)
					{
						if (is_string($size))
						{
							$definition .= ' ' . strtoupper($size) . ($type == 'integer' ? 'INT' : $type);
						}
						else
						{
							$definition .= ' ' . $type . '(' . $size . ')';
						}
					}
					else
					{
						$definition .= ' ' . $type;
					}

					if (($type == 'integer') && !empty($params['unsigned']))
					{
						$definition .= ' UNSIGNED';
					}
				}
				break;

				case 'boolean':
				case 'date':
				case 'datetime':
				case 'time':
				case 'timestamp':
				case 'year':
				{
					$definition .= ' ' . $type;
				}
				break;

				case 'enum':
				{
					$enum = [];

					foreach ((array) $size as $identifier)
					{
						$enum[] = '\'' . $identifier . '\'';
					}

					$definition .= ' ' . $type . '(' . implode(', ', $enum) . ')';
				}
				break;

				case 'double':
				case 'float':
				{
					$definition .= ' ' . $type;

					if ($size)
					{
						$definition .= '(' . implode(', ', (array) $size) . ')';
					}
				}
				break;

				default:
				{
					throw new \InvalidArgumentException("Unsupported type <q>{$type}</q> for row <q>{$identifier}</q>.");
				}
				break;
			}

			#
			# null
			#

			if (empty($params['null']))
			{
				$definition .= ' NOT NULL';
			}
			else
			{
				$definition .= ' NULL';
			}

			#
			# default
			#

			if (!empty($params['default']))
			{
				$default = $params['default'];

				$definition .= ' DEFAULT ' . ($default{strlen($default) - 1} == ')' || $default == 'CURRENT_TIMESTAMP' ? $default : '"' . $default . '"');
			}

			#
			# serial, unique
			#

			if (!empty($params['auto increment']))
			{
				if ($driver_name == 'mysql')
				{
					$definition .= ' AUTO_INCREMENT';
				}
				else if ($driver_name == 'sqlite')
				{
// 					$definition .= ' PRIMARY KEY';
// 					unset($schema['primary']);
				}
			}
			else if (!empty($params['unique']))
			{
				$unique_id = $params['unique'];

				if ($unique_id === true)
				{
					$definition .= ' UNIQUE';
				}
				else
				{
					$unique_list[$unique_id][] = $identifier;
				}
			}

			$parts[] = $definition;
		}

		#
		# primary key
		#

		if ($schema['primary'])
		{
			$keys = (array) $schema['primary'];

			$parts[] = 'PRIMARY KEY (' . implode(', ', $this->quote_identifier($keys)) . ')';
		}

		#
		# indexes
		#

		if (isset($schema['indexes']) && $driver_name == 'mysql')
		{
			foreach ($schema['indexes'] as $key => $identifiers)
			{
				$definition = 'INDEX ';

				if (!is_numeric($key))
				{
					$definition .= $this->quote_identifier($key) . ' ';
				}

				$definition .= '(' . implode(',', $this->quote_identifier((array) $identifiers)) . ')';

				$parts[] = $definition;
			}
		}

		$table_name = $this->table_name_prefix . $unprefixed_name;
		$statement = 'CREATE TABLE `' . $table_name . '` (' . implode(', ', $parts) . ')';

		if ($driver_name == 'mysql')
		{
			$statement .= ' CHARACTER SET ' . $this->charset . ' COLLATE ' . $this->collate;
		}

		$rc = ($this->exec($statement) !== false);

		if (!$rc)
		{
			return $rc;
		}

		if (isset($schema['indexes']) && $driver_name == 'sqlite')
		{
			#
			# SQLite: now that the table has been created, we can add indexes
			#

			foreach ($schema['indexes'] as $key => $identifiers)
			{
				$statement = 'CREATE INDEX IF NOT EXISTS `' . $key . '` ON ' . $table_name;

				$identifiers = (array) $identifiers;

				foreach ($identifiers as &$identifier)
				{
					$identifier = '`' . $identifier . '`';
				}

				$statement .= ' (' . implode(',', $identifiers) . ')';

				$this->exec($statement);
			}
		}

		#
		# UNIQUE indexes
		#

		foreach ($unique_list as $unique_id => $columns)
		{
			$columns = implode(", ", $this->quote_identifier($columns));
			$statement = "ALTER TABLE `$table_name` ADD UNIQUE `$unique_id` ($columns)";

			$this->exec($statement);
		}

		return $rc;
	}

	/**
	 * Checks if a specified table exists in the database.
	 *
	 * @param string $unprefixed_name The unprefixed name of the table.
	 *
	 * @return bool `true` if the table exists, `false` otherwise.
	 */
	public function table_exists($unprefixed_name)
	{
		$name = $this->table_name_prefix . $unprefixed_name;

		if ($this->driver_name == 'sqlite')
		{
			$tables = $this
			->query('SELECT name FROM sqlite_master WHERE type = "table" AND name = ?', [ $name ])
			->fetchAll(self::FETCH_COLUMN);

			return !empty($tables);
		}

		$tables = $this->query('SHOW TABLES')->fetchAll(self::FETCH_COLUMN);

		return in_array($name, $tables);
	}

	/**
	 * Optimizes the tables of the database.
	 */
	public function optimize()
	{
		if ($this->driver_name == 'sqlite')
		{
			$this->exec('VACUUM');
		}
		else if ($this->driver_name == 'mysql')
		{
			$tables = $this->query('SHOW TABLES')->fetchAll(self::FETCH_COLUMN);

			$this->exec('OPTIMIZE TABLE ' . implode(', ', $tables));
		}
	}
}
