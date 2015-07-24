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
 * @property-read Driver $driver
 * @property-read string $driver_name Name of the PDO driver.
 * @property-read string $id Identifier of the database connection.
 * @property-read string $table_name_prefix The prefix to prepend to every table name.
 */
class Connection extends \PDO implements Driver
{
	use AccessorTrait;

	static private $drivers_mapping = [

		'mysql' => Driver\MySQLDriver::class,
		'sqlite' => Driver\SQLiteDriver::class

	];

	/**
	 * Connection identifier.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * @return string
	 */
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

	/**
	 * @return string
	 */
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

	/**
	 * @return string
	 */
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

	/**
	 * @return string
	 */
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

	/**
	 * @return string
	 */
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

	/**
	 * @return string
	 */
	protected function get_driver_name()
	{
		return $this->driver_name;
	}

	/**
	 * @var Driver
	 */
	private $driver;

	/**
	 * @return Driver
	 */
	protected function lazy_get_driver()
	{
		return $this->resolve_driver($this->driver_name);
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
	 * @var array[]
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
		unset($this->driver);

		$this->driver_name = $driver_name = $this->resolve_driver_name($dsn);
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
	 * Resolves driver class.
	 *
	 * @param string $driver_name
	 *
	 * @return string
	 *
	 * @throws DriverNotDefined
	 */
	protected function resolve_driver_class($driver_name)
	{
		if (empty(self::$drivers_mapping[$driver_name]))
		{
			throw new DriverNotDefined($driver_name);
		}

		return self::$drivers_mapping[$driver_name];
	}

	/**
	 * Resolves a {@link Driver} implementation.
	 *
	 * @param string $driver_name
	 *
	 * @return Driver
	 */
	protected function resolve_driver($driver_name)
	{
		$driver_class = $this->resolve_driver_class($driver_name);

		return new $driver_class(function() { return $this; });
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
	 * @see \PDO::beginTransaction()
	 */
	public function begin()
	{
		return $this->beginTransaction();
	}

	/**
	 * @inheritdoc
	 */
	public function quote_string($string)
	{
		return $this->driver->quote_string($string);
	}

	/**
	 * @inheritdoc
	 */
	public function quote_identifier($identifier)
	{
		return $this->driver->quote_identifier($identifier);
	}

	/**
	 * @inheritdoc
	 */
	public function cast_value($value, $type = null)
	{
		return $this->driver->cast_value($value, $type);
	}

	/**
	 * @inheritdoc
	 */
	public function create_table($unprefixed_name, Schema $schema)
	{
		$this->driver->create_table($unprefixed_name, $schema);
	}

	/**
	 * @inheritdoc
	 */
	public function create_indexes($unprefixed_table_name, Schema $schema)
	{
		$this->driver->create_indexes($unprefixed_table_name, $schema);
	}

	/**
	 * @inheritdoc
	 */
	public function create_unique_indexes($unprefixed_table_name, Schema $schema)
	{
		$this->driver->create_unique_indexes($unprefixed_table_name, $schema);
	}

	/**
	 * @inheritdoc
	 */
	public function table_exists($unprefixed_name)
	{
		return $this->driver->table_exists($unprefixed_name);
	}

	/**
	 * @inheritdoc
	 */
	public function optimize()
	{
		$this->driver->optimize();
	}
}
