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
	 * Creates a table of the specified name and schema.
	 *
	 * @param string $unprefixed_name The unprefixed name of the table.
	 * @param Schema $schema The schema of the table.
	 *
	 * @return bool
	 */
	public function create_table($unprefixed_name, Schema $schema)
	{
		$driver_name = $this->driver_name;

		$rendered_columns = [];

		foreach ($schema as $column_id => $column)
		{
			$quoted_column_id = $this->quote_identifier($column_id);

			if ($column->primary && $driver_name == 'sqlite')
			{
				$rendered_columns[$column_id] = "$quoted_column_id INTEGER NOT NULL";

				continue;
			}

			$rendered_columns[$column_id] = "$quoted_column_id $column";
		}

		$primary = $schema->primary;

		if ($primary)
		{
			$rendered_primary = (array) $primary;
			$rendered_primary = $this->quote_identifier($rendered_primary);
			$rendered_primary = implode(', ', $rendered_primary);

			$rendered_columns[] = "PRIMARY KEY($rendered_primary)";
		}

		# create table sql

		$table_name = $this->table_name_prefix . $unprefixed_name;
		$quoted_table_name = $this->quote_identifier($table_name);

		$statement = "CREATE TABLE $quoted_table_name\n(\n\t" . implode(",\n\t", $rendered_columns) . "\n)";

		if ($driver_name == 'mysql')
		{
			$statement .= " CHARACTER SET $this->charset  COLLATE $this->collate";
		}

		$rc = ($this->exec($statement) !== false);

		$indexes = $schema->indexes;

		if ($indexes)
		{
			foreach ($indexes as $index_id => $column_names)
			{
				$column_names = $this->quote_identifier($column_names);
				$rendered_column_names = implode(', ', $column_names);
				$quoted_index_id = $this->quote_identifier($index_id);

				$this->exec("CREATE INDEX $quoted_index_id ON $quoted_table_name ($rendered_column_names)");
			}
		}

		$indexes = $schema->unique_indexes;

		if ($indexes)
		{
			echo "\n\n\n$statement\n\n\n";

			foreach ($indexes as $index_id => $column_names)
			{
				$column_names = $this->quote_identifier($column_names);
				$rendered_column_names = implode(', ', $column_names);
				$quoted_index_id = $this->quote_identifier($index_id);

				$this->exec("CREATE UNIQUE INDEX $quoted_index_id ON $quoted_table_name ($rendered_column_names)");
			}
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
