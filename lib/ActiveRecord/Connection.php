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
use PDO;
use PDOException;

use function explode;
use function strtr;

/**
 * A connection to a database.
 *
 * @property-read PDO pdo
 * @property-read string $charset The character set used to communicate with the database. Defaults
 *     to "utf8".
 * @property-read string $collate The collation of the character set. Defaults to
 *     "utf8_general_ci".
 * @property-read Driver $driver
 * @property-read string $driver_name Name of the PDO driver.
 * @property-read string|null $id Identifier of the database connection.
 * @property-read string $table_name_prefix The prefix to prepend to every table name.
 */
class Connection implements Driver
{
	/**
	 * @uses get_pdo
	 * @uses get_id
	 * @uses get_table_name_prefix
	 * @uses get_charset
	 * @uses get_collate
	 * @uses get_timezone
	 * @uses get_driver_name
	 * @uses lazy_get_driver
	 */
	use AccessorTrait;

	private const DRIVERS_MAPPING = [

		'mysql' => Driver\MySQLDriver::class,
		'sqlite' => Driver\SQLiteDriver::class,

	];

	private function get_pdo(): PDO
	{
		return $this->pdo;
	}

	/**
	 * Connection identifier.
	 */
	private ?string $id;

	private function get_id(): ?string
	{
		return $this->id;
	}

	/**
	 * Prefix to prepend to every table name.
	 *
	 * If set to "dev", all table names will be named like "dev_nodes", "dev_contents", etc.
	 * This is a convenient way of creating a namespace for tables in a shared database.
	 * By default, the prefix is the empty string, that is there is not prefix.
	 */
	private string $table_name_prefix = Options::DEFAULT_TABLE_NAME_PREFIX;

	private function get_table_name_prefix(): string
	{
		return $this->table_name_prefix;
	}

	/**
	 * Charset for the connection. Also used to specify the charset while creating tables.
	 */
	private string $charset = Options::DEFAULT_CHARSET;

	private function get_charset(): string
	{
		return $this->charset;
	}

	/**
	 * Used to specify the collate while creating tables.
	 */
	private string $collate = Options::DEFAULT_COLLATE;

	private function get_collate(): string
	{
		return $this->collate;
	}

	/**
	 * Timezone of the connection.
	 */
	private string $timezone = Options::DEFAULT_TIMEZONE;

	private function get_timezone(): string
	{
		return $this->timezone;
	}

	/**
	 * Driver name for the connection.
	 */
	private string $driver_name;

	private function get_driver_name(): string
	{
		return $this->driver_name;
	}

	private Driver $driver;

	private function lazy_get_driver(): Driver
	{
		return $this->resolve_driver($this->driver_name);
	}

	/**
	 * The number of database queries and executions, used for statistics purpose.
	 */
	public int $queries_count = 0;

	/**
	 * The number of micro seconds spent per request.
	 *
	 * @var array[]
	 */
	public array $profiling = [];

	private PDO $pdo;

	/**
	 * Establish a connection to a database.
	 *
	 * Custom options can be specified using the driver-specific connection options. See
	 * {@link Options}.
	 *
	 * @link http://www.php.net/manual/en/pdo.construct.php
	 * @link http://dev.mysql.com/doc/refman/5.5/en/time-zone-support.html
	 *
	 * @param array<string, mixed> $options
	 */
	public function __construct(
		string $dsn,
		string $username = null,
		string $password = null,
		array $options = []
	) {
		unset($this->driver);

		$this->driver_name = $this->resolve_driver_name($dsn);
		$this->apply_options($options);
		$this->before_connection($options);

		$this->pdo = new PDO($dsn, $username, $password, $options);

		$this->after_connection();
	}

	/**
	 * Alias to {@link query}.
	 */
	public function __invoke(...$args): Statement
	{
		return $this->query(...$args);
	}

	/**
	 * Resolve the driver name from the DSN string.
	 */
	protected function resolve_driver_name(string $dsn): string
	{
		return explode(':', $dsn, 2)[0];
	}

	/**
	 * Resolves driver class.
	 *
	 * @throws DriverNotDefined
	 */
	private function resolve_driver_class(string $driver_name): string
	{
		return self::DRIVERS_MAPPING[$driver_name] ?? throw new DriverNotDefined($driver_name);
	}

	/**
	 * Resolves a {@link Driver} implementation.
	 */
	private function resolve_driver(string $driver_name): Driver
	{
		$driver_class = $this->resolve_driver_class($driver_name);

		return new $driver_class(
			function () {
				return $this;
			}
		);
	}

	/**
	 * Applies options to the instance.
	 *
	 * @param array<string, mixed> $options
	 */
	private function apply_options(array $options): void
	{
		$options = Options::normalize($options);

		$this->id = $options[Options::ID];
		$this->table_name_prefix = $options[Options::TABLE_NAME_PREFIX];

		if ($this->table_name_prefix) {
			$this->table_name_prefix .= '_';
		}

		[ $this->charset, $this->collate ] = extract_charset_and_collate(
			$options[Options::CHARSET_AND_COLLATE]
		);

		$this->timezone = $options[Options::TIMEZONE];
	}

	/**
	 * Called before the connection.
	 *
	 * May alter the options according to the driver.
	 *
	 * @param array<string, mixed> $options
	 */
	private function before_connection(array &$options): void
	{
		if ($this->driver_name != 'mysql') {
			return;
		}

		$init_command = 'SET NAMES ' . $this->charset;

		if ($this->timezone) {
			$init_command .= ', time_zone = "' . $this->timezone . '"';
		}

		$options += [

			PDO::MYSQL_ATTR_INIT_COMMAND => $init_command,

		];
	}

	private function after_connection(): void
	{
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Overrides the method to resolve the statement before it is prepared, then set its fetch
	 * mode and connection.
	 *
	 * @param string $statement Query statement.
	 * @param array<string, mixed> $options
	 *
	 * @return Statement The prepared statement.
	 *
	 * @throws StatementNotValid if the statement cannot be prepared.
	 */
	public function prepare(string $statement, array $options = []): Statement
	{
		$statement = $this->resolve_statement($statement);

		try {
			$statement = $this->pdo->prepare($statement, $options);
		} catch (PDOException $e) {
			throw new StatementNotValid($statement, 500, $e);
		}

		if (isset($options['mode'])) {
			$mode = (array)$options['mode'];

			$statement->setFetchMode(...$mode);
		}

		return new Statement($statement, $this);
	}

	/**
	 * Overrides the method in order to prepare (and resolve) the statement and execute it with
	 * the specified arguments and options.
	 *
	 * @param array<string|int, mixed> $args
	 * @param array<string, mixed> $options
	 */
	public function query(string $statement, array $args = [], array $options = []): Statement
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
	 * @throws StatementNotValid if the statement cannot be executed.
	 */
	public function exec(string $statement): false|int
	{
		$statement = $this->resolve_statement($statement);

		try {
			$this->queries_count++;

			return $this->pdo->exec($statement);
		} catch (PDOException $e) {
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
	 */
	public function resolve_statement(string $statement): string
	{
		return strtr($statement, [
			'{prefix}' => $this->table_name_prefix,
			'{charset}' => $this->charset,
			'{collate}' => $this->collate,
		]);
	}

	/**
	 * Alias for the `beginTransaction()` method.
	 *
	 * @see \PDO::beginTransaction()
	 */
	public function begin(): bool
	{
		return $this->pdo->beginTransaction();
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function quote_string(string|array $string): string|array
	{
		return $this->driver->quote_string($string);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function quote_identifier(string|array $identifier): string|array
	{
		return $this->driver->quote_identifier($identifier);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function cast_value(mixed $value, string $type = null): mixed
	{
		return $this->driver->cast_value($value, $type);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function render_column(SchemaColumn $column): string
	{
		return $this->driver->render_column($column);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function create_table(string $unprefixed_table_name, Schema $schema): void
	{
		$this->driver->create_table($unprefixed_table_name, $schema);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function create_indexes(string $unprefixed_table_name, Schema $schema): void
	{
		$this->driver->create_indexes($unprefixed_table_name, $schema);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function create_unique_indexes(string $unprefixed_table_name, Schema $schema): void
	{
		$this->driver->create_unique_indexes($unprefixed_table_name, $schema);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function table_exists(string $unprefixed_name): bool
	{
		return $this->driver->table_exists($unprefixed_name);
	}

	/**
	 * @inheritdoc
	 *
	 * @codeCoverageIgnore
	 */
	public function optimize(): void
	{
		$this->driver->optimize();
	}
}
