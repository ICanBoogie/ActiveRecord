<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

use function explode;
use function strtr;

/**
 * A connection to a database.
 *
 * @see self::get_last_insert_id()
 * @property-read int $last_insert_id
 *     Returns the ID of the last inserted row, or the last value from a sequence object,
 *     depending on the underlying driver.
 */
class Connection
{
    /**
     * @see self::get_last_insert_id()
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
    public readonly Driver $driver;

    public readonly PDO $pdo;
    public readonly ConnectionTelemetry $telemetry;

    /**
     * Establish a connection to a database.
     *
     * Custom options can be specified using the driver-specific connection options. See
     * {@see Options}.
     *
     * @link http://www.php.net/manual/en/pdo.construct.php
     * @link http://dev.mysql.com/doc/refman/5.5/en/time-zone-support.html
     */
    public function __construct(ConnectionDefinition $definition)
    {
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
        $this->driver = $this->resolve_driver($this->driver_name);
        $this->telemetry = new ConnectionTelemetry($this->id);

        $options = $this->make_options();

        $this->pdo = new PDO($dsn, $definition->username, $definition->password, $options);

        $this->after_connection();
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
     * @return class-string<Driver>
     * @throws DriverNotDefined
     *
     */
    private function resolve_driver_class(string $driver_name): string
    {
        return self::DRIVERS_MAPPING[$driver_name]
            ?? throw new DriverNotDefined($driver_name); // @phpstan-ignore-line
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
     * @throws StatementNotValid if the statement cannot be prepared.
     */
    public function prepare(string $statement): Statement
    {
        $statement = $this->resolve_statement($statement);

        try {
            $statement = $this->pdo->prepare($statement);
        } catch (PDOException $e) {
            throw new StatementNotValid($statement, original: $e);
        }

        return new Statement($statement, $this->telemetry);
    }

    /**
     * Overrides the method to prepare (and resolve) the statement and execute it with
     * the specified arguments and options.
     *
     * @param mixed[] $args
     */
    public function query(string $statement, array $args = []): Statement
    {
        $statement = $this->prepare($statement);

        $this->telemetry->record_execute_duration(
            $statement,
            static fn() => $statement->execute($args)
        );

        return $statement;
    }

    /**
     * Executes a statement.
     *
     * The statement is resolved using the {@see resolve_statement()} method before it is
     * executed.
     *
     * The execution of the statement is wrapped in a try/catch block.
     * When a {@see PDOException} is caught, it is wrapped with {@see StatementNotValid}.
     *
     * Using this method increments the `queries_count` stat.
     *
     * @return false|int @FIXME https://github.com/sebastianbergmann/phpunit/issues/4735
     * @throws StatementNotValid if the statement cannot be executed.
     */
    public function exec(string $statement): bool|int
    {
        $statement = $this->resolve_statement($statement);

        try {
            // @phpstan-ignore-next-line
            return $this->telemetry->record_execute_duration(
                $statement,
                fn() => $this->pdo->exec($statement)
            );
        } catch (PDOException $e) {
            throw new StatementNotValid($statement, original: $e);
        }
    }

    public function get_last_insert_id(): int
    {
        $id = $this->pdo->lastInsertId();

        if ($id === false) {
            throw new RuntimeException("Unable to retrieve last inserted ID");
        }

        return (int)$id;
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
     * @see PDO::quote()
     */
    public function quote(string $string, int $type = PDO::PARAM_STR): string
    {
        $quoted = $this->pdo->quote($string, $type);

        // @phpstan-ignore-next-line
        if ($quoted === false) {
            throw new InvalidArgumentException("Unsupported quote type: $type");
        }

        return $quoted;
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
     * @throws Throwable
     */
    public function create_table(string $unprefixed_table_name, Schema $schema): void
    {
        $this->driver->create_table($this->table_name_prefix . $unprefixed_table_name, $schema);
    }

    /**
     * Determines if a table exists in the database.
     */
    public function table_exists(string $unprefixed_name): bool
    {
        return $this->driver->table_exists($this->table_name_prefix . $unprefixed_name);
    }

    public function optimize(): void
    {
        $this->driver->optimize();
    }
}
