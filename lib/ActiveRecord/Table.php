<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\TableDefinition;
use LogicException;
use RuntimeException;
use Throwable;

use function array_combine;
use function array_diff_key;
use function array_fill;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_string;
use function strtr;

/**
 * A representation of a database table.
 */
class Table
{
    /**
     * Name of the table, without the prefix defined by the connection.
     *
     * @var non-empty-string
     */
    public readonly string $unprefixed_name;

    /**
     * Name of the table, including the prefix defined by the connection.
     *
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * Alias for the table's name, which can be defined using the {@link ALIAS} attribute
     * or automatically created.
     *
     * This is the value for the "{primary}" placeholder.
     *
     * @var non-empty-string
     */
    public readonly string $alias;
    public readonly Schema $schema;

    /**
     * Primary key of the table, retrieved from the schema defined using the {@link SCHEMA} attribute.
     *
     * @var non-empty-string|non-empty-array<non-empty-string>|null
     */
    public readonly array|string|null $primary;

    /**
     * SQL fragment for the FROM clause of the query, made of the table's name and alias and those
     * of the hierarchy.
     */
    public string $update_join;

    public function make_update_join(): string
    {
        $join = '';
        $parent = $this->parent;

        while ($parent) {
            assert(is_string($this->primary));

            $join .= " INNER JOIN `$parent->name` `$parent->alias` USING(`$this->primary`)";
            $parent = $parent->parent;
        }

        return $join;
    }

    /**
     * SQL fragment for the FROM clause of the query, made of the table's name and alias and those
     * of the related tables, inherited and implemented.
     *
     * This is the value for the `{self_and_related}` placeholder.
     */
    public string $select_join;

    private function make_select_join(): string
    {
        return "`$this->alias`" . $this->update_join;
    }

    public Schema $extended_schema;

    /**
     * Returns the extended schema.
     */
    private function make_extended_schema(): Schema
    {
        $table = $this;
        $columns = [];

        while ($table) {
            $columns[] = $table->schema->columns;

            $table = $table->parent;
        }

        $columns = array_reverse($columns);
        $columns = array_merge(...$columns);

        return new Schema($columns, primary: $this->primary);
    }

    public function __construct(
        public readonly Connection $connection,
        TableDefinition $definition,
        public readonly ?self $parent = null,
    ) {
        $this->unprefixed_name = $definition->name;
        $this->name = $connection->table_name_prefix . $this->unprefixed_name;
        $this->alias = $definition->alias;
        $this->schema = $definition->schema;
        $this->primary = $this->schema->primary;
        $this->extended_schema = $this->parent
            ? $this->make_extended_schema()
            : $this->schema;
        $this->update_join = $this->make_update_join();
        $this->select_join = $this->make_select_join();
    }

    /**
     * Interface to the connection's query() method.
     *
     * The statement is resolved using the resolve_statement() method and prepared.
     *
     * @param non-empty-string $query
     * @param mixed[] $args
     */
    public function __invoke(string $query, array $args = []): Statement
    {
        $statement = $this->prepare($query);

        return $statement($args);
    }

    /*
    **

    INSTALL

    **
    */

    /**
     * Creates table.
     *
     * @throws Throwable if install fails.
     */
    public function install(): void
    {
        $this->connection->create_table($this->unprefixed_name, $this->schema);
    }

    /**
     * Drops table.
     *
     * @throws Throwable if uninstall fails.
     */
    public function uninstall(): void
    {
        $this->drop();
    }

    /**
     * Checks whether the table is installed.
     */
    public function is_installed(): bool
    {
        return $this->connection->table_exists($this->unprefixed_name);
    }

    /**
     * Resolves statement placeholders.
     *
     * The following placeholders are replaced:
     *
     * - `{alias}`: The alias of the table.
     * - `{prefix}`: The prefix used for the tables of the connection.
     * - `{primary}`: The primary key of the table.
     * - `{self}`: The name of the table.
     * - `{self_and_related}`: The escaped name of the table and the possible JOIN clauses.
     *
     * Note: If the table has a multi-column primary keys `{primary}` is replaced by
     * `__multi-column_primary__<concatenated_columns>` where `<concatenated_columns>` is the columns
     * concatenated with an underscore ("_") as separator. For instance, if a table primary key is
     * made of columns "p1" and "p2", `{primary}` is replaced by `__multi-column_primary__p1_p2`.
     * It's not very helpful, but we still have to decide what to do with this.
     *
     * @param string $statement The statement to resolve.
     */
    public function resolve_statement(string $statement): string
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
     * The statement is resolved by the {@see resolve_statement()} method before the call is
     * forwarded.
     *
     * @param non-empty-string $query
     */
    public function prepare(string $query): Statement
    {
        $query = $this->resolve_statement($query);

        return $this->connection->prepare($query);
    }

    /**
     * Executes a statement.
     *
     * The statement is prepared by the {@see prepare()} method before it is executed.
     *
     * @param non-empty-string $query
     * @param array<int|string, mixed> $args
     */
    public function execute(string $query, array $args = []): Statement
    {
        $statement = $this->prepare($query);

        return $statement($args);
    }

    /**
     * Filters mass assignment values.
     *
     * @param array<string, mixed> $values
     *
     * @return array{ array<int|string|null>, array<string, string>, array<string> }
     */
    private function filter_values(array $values, bool $extended = false): array
    {
        $filtered = [];
        $holders = [];
        $identifiers = [];
        $schema = $extended ? $this->extended_schema : $this->schema;
        $driver = $this->connection->driver;

        foreach ($schema->filter_values($values) as $identifier => $value) {
            $quoted_identifier = $driver->quote_identifier($identifier);

            $filtered[] = $driver->cast_value($value);
            $holders[$identifier] = "$quoted_identifier = ?";
            $identifiers[] = $quoted_identifier;
        }

        return [ $filtered, $holders, $identifiers ];
    }

    /**
     * Saves values.
     *
     * @param array<string, mixed> $values
     * @param array<string, mixed> $options
     *
     * @throws Throwable
     */
    public function save(array $values, ?int $id = null, array $options = []): int|false
    {
        // TODO: If we have a parent, we should do the changes in a transaction.

        if ($id) {
            $this->update($values, $id);

            return $id;
        }

        return $this->save_callback($values, $id, $options);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $options
     */
    private function save_callback(array $values, ?int $id = null, array $options = []): int
    {
        assert(count($values) > 0);

        if ($id) {
            $this->update($values, $id);

            return $id;
        }

        $parent_id = 0;

        if ($this->parent) {
            $parent_id = $this->parent->save_callback($values, options: $options)
                ?: throw new RuntimeException(
                    "Parent save failed: {$this->parent->name} returning $parent_id"
                );

            assert(is_string($this->primary));

            $values[$this->primary] = $parent_id;
        }

        $driver_name = $this->connection->driver_name;

        [ $filtered, $holders, $identifiers ] = $this->filter_values($values);

        // FIXME: ALL THIS NEED REWRITE !

        if ($holders) {
            // If we have a parent, its primary key values must be used.

            if ($driver_name === 'mysql') {
                if ($parent_id && empty($holders[$this->primary])) {
                    $filtered[] = $parent_id;
                    $holders[] = '`{primary}` = ?';
                }

                $statement = 'INSERT INTO `{self}` SET ' . implode(', ', $holders);
                $statement = $this->prepare($statement);

                $statement->execute($filtered);
            } elseif ($driver_name === 'sqlite') {
                $this->insert($values);
            } else {
                throw new LogicException("Don't know what to do with $driver_name");
            }
        } elseif ($parent_id) {
            #
            # a new entry has been created, but we don't have any other fields then the primary key
            #

            if (empty($identifiers[$this->primary])) {
                $identifiers[] = '`{primary}`';
                $filtered[] = $parent_id;
            }

            $identifiers = implode(', ', $identifiers);
            $placeholders = implode(', ', array_fill(0, count($filtered), '?'));

            $statement = "INSERT INTO `{self}` ($identifiers) VALUES ($placeholders)";
            $statement = $this->prepare($statement);

            $statement->execute($filtered);
        }

        if ($parent_id) {
            return $parent_id;
        }

        return $this->connection->last_insert_id;
    }

    /**
     * Inserts values into the table.
     *
     * @param non-empty-array<string, mixed> $values The values to insert.
     * @param bool $ignore Optional value to ignore insert errors.
     * @param bool $upsert Optional value to update the row if there is a matching primary key.
     */
    public function insert(array $values, bool $ignore = false, bool $upsert = false): void
    {
        [ $values, $holders, $identifiers ] = $this->filter_values($values);

        if (!$values) {
            throw new LogicException("No values to insert");
        }

        $driver_name = $this->connection->driver_name;

        if ($driver_name == 'mysql') {
            $query = 'INSERT';

            if ($ignore) {
                $query .= ' IGNORE ';
            }

            $query .= ' INTO `{self}` SET ' . implode(', ', $holders);

            if ($upsert) {
                #
                # We use the same input values, but we take care of
                # removing the primary key and its corresponding value
                #

                $update_values = array_combine(array_keys($holders), $values);
                $update_holders = $holders;

                $primary = $this->primary;

                if (is_array($primary)) {
                    $flip = array_flip($primary);

                    $update_holders = array_diff_key($update_holders, $flip);
                    $update_values = array_diff_key($update_values, $flip);
                } else {
                    unset($update_holders[$primary]);
                    unset($update_values[$primary]);
                }

                $update_values = array_values($update_values);

                $query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $update_holders);

                $values = array_merge($values, $update_values);
            }
        } elseif ($driver_name == 'sqlite') {
            $holders = array_fill(0, count($identifiers), '?');

            $query = 'INSERT'
                . ($ignore | $upsert ? ' OR' : '')
                . ($ignore ? ' IGNORE' : '')
                . ($upsert ? ' REPLACE' : '')
                . ' INTO `{self}` (' . implode(', ', $identifiers) . ')'
                . ' VALUES (' . implode(', ', $holders) . ')';
        } else {
            throw new LogicException("Unsupported drive: $driver_name.");
        }

        $this->execute($query, $values);
    }

    /**
     * Update the values of an entry.
     *
     * Even if the entry is spread over multiple tables, all the tables are updated in a single
     * step.
     *
     * @param array<string, mixed> $values
     */
    public function update(array $values, int|string $key): void
    {
        #
        # SQLite doesn't support UPDATE with INNER JOIN.
        #

        if ($this->connection->driver_name == 'sqlite') {
            $table = $this;

            while ($table) {
                [ $table_values, $holders ] = $table->filter_values($values);

                if ($holders) {
                    $query = 'UPDATE `{self}` SET ' . implode(', ', $holders) . ' WHERE `{primary}` = ?';
                    $table_values[] = $key;

                    $table->execute($query, $table_values);
                }

                $table = $table->parent;
            }

            return;
        }

        [ $values, $holders ] = $this->filter_values($values, true);

        $query = "UPDATE `{self}` $this->update_join  SET " . implode(', ', $holders) . ' WHERE `{primary}` = ?';
        $values[] = $key;

        $this->execute($query, $values);
    }

    /**
     * Deletes a record.
     *
     * @param int|string|array<int|string> $key
     */
    public function delete(int|string|array $key): void
    {
        $this->parent?->delete($key);

        $where = 'WHERE ';

        if (is_array($this->primary)) {
            $parts = [];

            foreach ($this->primary as $identifier) {
                $parts[] = "`$identifier` = ?";
            }

            $where .= implode(' AND ', $parts);
        } else {
            $where .= '`{primary}` = ?';
        }

        $statement = $this->prepare('DELETE FROM `{self}` ' . $where);
        $statement((array)$key);
    }

    /**
     * Truncates table.
     *
     * @FIXME-20081223: what about extends ?
     */
    public function truncate(bool $reset_autoincrement = false): void
    {
        if ($this->connection->driver_name == 'sqlite') {
            $this->execute("DELETE FROM {self}");
            if ($reset_autoincrement) {
                $this->execute("DELETE FROM sqlite_sequence WHERE name = '{self}'");
            }
            $this->execute('vacuum');

            return;
        }

        $this->execute("TRUNCATE TABLE {self}");
        $this->execute("ALTER TABLE {self} AUTO_INCREMENT = 1");
    }

    /**
     * Drops table.
     *
     * @throws StatementNotValid when the table cannot be dropped.
     */
    public function drop(bool $if_exists = false): void
    {
        $query = 'DROP TABLE' . ($if_exists ? ' IF EXISTS ' : '') . ' `{self}`';

        $this->execute($query);
    }
}
