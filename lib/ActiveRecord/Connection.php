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
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use PDO;
use PDOException;
use Throwable;

use function explode;
use function strtr;

/**
 * A connection to a database.
 */
class Connection
{
    /**
     * @uses lazy_get_driver
     */
    use AccessorTrait;

    private const DRIVERS_MAPPING = [

        'mysql' => Driver\MySQLDriver::class,
        'sqlite' => Driver\SQLiteDriver::class,

    ];

    public readonly string $id;

    /**
     * Prefix to prepend to every table name.
     *
     * If set to "dev", all table names will be named like "dev_nodes", "dev_contents", etc.
     * This is a convenient way of creating a namespace for tables in a shared database.
     * By default, the prefix is the empty string, that is there is not prefix.
     */
    public readonly string $table_name_prefix;

    /**
     * Charset for the connection. Also used to specify the charset while creating tables.
     */
    public readonly string $charset;

    /**
     * Used to specify the collate while creating tables.
     */
    public readonly string $collate;

    /**
     * Timezone of the connection.
     */
    public readonly string $timezone;

    /**
     * Driver name for the connection.
     */
    public readonly string $driver_name;

    private Driver $driver;

    private function lazy_get_driver(): Driver
    {
        return $this->resolve_driver($this->driver_name);
    }

    /**
     * The number of database queries and executions, used for statistics purpose.
     */
    public int $queries_count = 0;
    public readonly PDO $pdo;

    /**
     * The number of micro seconds spent per request.
     *
     * @var array[]
     */
    public array $profiling = [];

    /**
     * Establish a connection to a database.
     *
     * Custom options can be specified using the driver-specific connection options. See
     * {@link Options}.
     *
     * @link http://www.php.net/manual/en/pdo.construct.php
     * @link http://dev.mysql.com/doc/refman/5.5/en/time-zone-support.html
     */
    public function __construct(ConnectionDefinition $definition)
    {
        unset($this->driver); // to trigger lazy loading

        $this->id = $definition->id;
        $dsn = $definition->dsn;

        $this->table_name_prefix = $definition->table_name_prefix
            ? $definition->table_name_prefix . '_'
            : '';

        [ $this->charset, $this->collate ] = extract_charset_and_collate(
            $definition->charset_and_collate ?? $definition::DEFAULT_CHARSET_AND_COLLATE
        );

        $this->timezone = $definition->time_zone;
        $this->driver_name = $this->resolve_driver_name($dsn);

        $options = $this->make_options();

        $this->pdo = new PDO($dsn, $definition->username, $definition->password, $options);

        $this->after_connection();
    }

    /**
     * Alias to {@link query}.
     */
    public function __invoke(mixed ...$args): Statement
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
     *
     * @return class-string<Driver>
     */
    private function resolve_driver_class(string $driver_name): string
    {
        return self::DRIVERS_MAPPING[$driver_name]
            ?? throw new DriverNotDefined($driver_name);
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
     * Called before the connection.
     *
     * May alter the options according to the driver.
     *
     * @return array<PDO::*, mixed>
     */
    private function make_options(): array
    {
        if ($this->driver_name != 'mysql') {
            return [];
        }

        $init_command = 'SET NAMES ' . $this->charset;
        $init_command .= ', time_zone = "' . $this->timezone . '"';

        return [

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
            $mode = (array) $options['mode'];

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
     * @return false|int @FIXME https://github.com/sebastianbergmann/phpunit/issues/4735
     * @throws StatementNotValid if the statement cannot be executed.
     */
    public function exec(string $statement): bool|int
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
     * @see PDO::beginTransaction
     */
    public function begin(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * @codeCoverageIgnore
     */
    public function quote_string(string $string): string
    {
        return $this->pdo->quote($string);
    }

    public function quote_identifier(string $identifier): string
    {
        return $this->driver->quote_identifier($identifier);
    }

    public function cast_value(mixed $value, string $type = null): mixed
    {
        return $this->driver->cast_value($value, $type);
    }

    /**
     * @param non-empty-string $unprefixed_table_name
     *
     * @throws Throwable
     */
    public function create_table(string $unprefixed_table_name, Schema $schema): void
    {
        $this->driver->create_table($this->table_name_prefix . $unprefixed_table_name, $schema);
    }

    /**
     * @codeCoverageIgnore
     */
    public function table_exists(string $unprefixed_name): bool
    {
        return $this->driver->table_exists($this->table_name_prefix . $unprefixed_name);
    }

    /**
     * @codeCoverageIgnore
     */
    public function optimize(): void
    {
        $this->driver->optimize();
    }
}
