<?php

namespace ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\Config\TableDefinition;
use LogicException;
use Throwable;

use function array_diff_key;
use function array_fill;
use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_push;
use function array_reverse;
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

            $join .= ' INNER JOIN ' . $this->quote_identifier($parent->name)
                . ' ' . $this->quote_identifier($parent->alias)
                . ' USING(' . $this->quote_identifier($this->primary) . ')';
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
        return $this->quote_identifier($this->alias) . $this->update_join;
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
     * Quotes an identifier using the connection's driver.
     */
    private function quote_identifier(string $identifier): string
    {
        return $this->connection->quote_identifier($identifier);
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
            '{self_and_related}' => $this->quote_identifier($this->name)
                . ($this->select_join ? " $this->select_join" : '')

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
     * Filters values against the schema and casts them for the database.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, int|string|null>
     *     Where _key_ is a column name.
     */
    private function filter_values(array $values, bool $extended = false): array
    {
        $schema = $extended ? $this->extended_schema : $this->schema;
        $driver = $this->connection->driver;

        return array_map(function ($value) use ($driver) {
            return $driver->cast_value($value);
        }, $schema->filter_values($values));
    }

    /**
     * Renders a list of quoted identifiers.
     *
     * @param string[] $columns
     */
    private function render_identifiers(array $columns): string
    {
        return implode(', ', array_map($this->quote_identifier(...), $columns));
    }

    /**
     * Renders `column = ?` assignments.
     *
     * @param string[] $columns
     */
    private function render_assignments(array $columns): string
    {
        return implode(', ', array_map(fn(string $column) => $this->quote_identifier($column) . ' = ?', $columns));
    }

    /**
     * Saves values.
     *
     * If `$id` is defined, the matching row is updated. Otherwise, a new row is inserted. When the
     * table has a parent, the values are spread over the tables of the hierarchy.
     *
     * @param array<string, mixed> $values
     *
     * @return int The primary key of the row.
     *
     * @throws Throwable
     */
    public function save(array $values, ?int $id = null): int
    {
        if ($id !== null) {
            $this->update($values, $id);

            return $id;
        }

        if (!$this->parent) {
            return $this->insert_and_get_id($values);
        }

        return $this->in_transaction(fn() => $this->insert_and_get_id($values));
    }

    /**
     * Inserts values into the tables of the hierarchy, from the root down, then returns the
     * primary key of the new row, which is the one generated by the root table.
     *
     * @param array<string, mixed> $values
     */
    private function insert_and_get_id(array $values): int
    {
        if (!$this->parent) {
            $this->insert($values);

            return $this->connection->last_insert_id;
        }

        assert(is_string($this->primary));

        $id = $this->parent->insert_and_get_id($values);
        $values[$this->primary] = $id;

        $this->insert($values);

        return $id;
    }

    /**
     * Runs a callback in a transaction, unless one is already active.
     *
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     *
     * @throws Throwable
     */
    private function in_transaction(Closure $callback): mixed
    {
        $pdo = $this->connection->pdo;

        if ($pdo->inTransaction()) {
            return $callback();
        }

        $pdo->beginTransaction();

        try {
            $result = $callback();
        } catch (Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        $pdo->commit();

        return $result;
    }

    /**
     * Inserts values into the table.
     *
     * Values that don't match a column are discarded.
     *
     * @param array<string, mixed> $values The values to insert.
     * @param bool $ignore Ignore the row if it conflicts with an existing one. On MySQL, other
     *     errors, such as data truncation, are ignored as well.
     * @param bool $upsert Update the existing row if the primary key conflicts.
     *
     * @throws LogicException if there's no value to insert, or if both `$ignore` and `$upsert`
     *     are requested.
     */
    public function insert(array $values, bool $ignore = false, bool $upsert = false): void
    {
        if ($ignore && $upsert) {
            throw new LogicException("Unable to insert, `ignore` and `upsert` are mutually exclusive");
        }

        $values = $this->filter_values($values);

        if (!$values) {
            throw new LogicException("No values to insert");
        }

        $columns = array_keys($values);
        $args = array_values($values);
        $is_mysql = $this->connection->driver_name === 'mysql';

        $query = 'INSERT' . ($ignore && $is_mysql ? ' IGNORE' : '')
            . ' INTO ' . $this->quote_identifier($this->name)
            . ' (' . $this->render_identifiers($columns) . ')'
            . ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';

        if ($upsert) {
            $query .= $this->render_upsert($values, $args);
        } elseif ($ignore && !$is_mysql) {
            $query .= ' ON CONFLICT DO NOTHING';
        }

        $this->execute($query, $args);
    }

    /**
     * Renders the clause that turns an insert into an upsert.
     *
     * Every column that isn't part of the primary key is updated. If every column is part of the
     * primary key, there's nothing to update, and the existing row is kept as is.
     *
     * @param array<string, int|string|null> $values
     * @param array<int|string|null> $args
     *     The statement arguments, MySQL needs the updated values to be bound a second time.
     */
    private function render_upsert(array $values, array &$args): string
    {
        $primary = (array) ($this->primary
            ?? throw new LogicException("Unable to upsert, table `$this->name` doesn't have a primary key"));
        $update = array_diff_key($values, array_flip($primary));

        if ($this->connection->driver_name === 'mysql') {
            if (!$update) {
                // MySQL has no DO NOTHING, so we assign a key column to itself.
                $key = $this->quote_identifier($primary[0]);

                return " ON DUPLICATE KEY UPDATE $key = $key";
            }

            array_push($args, ...array_values($update));

            return ' ON DUPLICATE KEY UPDATE ' . $this->render_assignments(array_keys($update));
        }

        $conflict = ' ON CONFLICT (' . $this->render_identifiers($primary) . ')';

        if (!$update) {
            return "$conflict DO NOTHING";
        }

        $set = array_map(function (string $column): string {
            $quoted = $this->quote_identifier($column);

            return "$quoted = excluded.$quoted";
        }, array_keys($update));

        return "$conflict DO UPDATE SET " . implode(', ', $set);
    }

    /**
     * Updates the values of an entry.
     *
     * If the entry is spread over multiple tables, each table is updated with its own values, in a
     * transaction.
     *
     * @param array<string, mixed> $values
     *
     * @throws Throwable
     */
    public function update(array $values, int|string $key): void
    {
        if (!$this->parent) {
            $this->update_own_values($values, $key);

            return;
        }

        $this->in_transaction(function () use ($values, $key): void {
            for ($table = $this; $table; $table = $table->parent) {
                $table->update_own_values($values, $key);
            }
        });
    }

    /**
     * Updates the columns of this table, ignoring those of the parent tables.
     *
     * @param array<string, mixed> $values
     */
    private function update_own_values(array $values, int|string $key): void
    {
        assert(is_string($this->primary));

        $values = $this->filter_values($values);

        if (!$values) {
            return;
        }

        $query = 'UPDATE ' . $this->quote_identifier($this->name)
            . ' SET ' . $this->render_assignments(array_keys($values))
            . ' WHERE ' . $this->quote_identifier($this->primary) . ' = ?';

        $this->execute($query, [ ...array_values($values), $key ]);
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
                $parts[] = $this->quote_identifier($identifier) . ' = ?';
            }

            $where .= implode(' AND ', $parts);
        } else {
            assert(is_string($this->primary));

            $where .= $this->quote_identifier($this->primary) . ' = ?';
        }

        $statement = $this->prepare('DELETE FROM ' . $this->quote_identifier($this->name) . ' ' . $where);
        $statement((array)$key);
    }

    /**
     * Truncates table.
     *
     * @FIXME-20081223: what about extends ?
     */
    public function truncate(bool $reset_autoincrement = false): void
    {
        $driver_name = $this->connection->driver_name;

        if ($driver_name == 'sqlite') {
            $this->execute("DELETE FROM " . $this->quote_identifier($this->name));
            if ($reset_autoincrement) {
                $this->execute("DELETE FROM sqlite_sequence WHERE name = '" . $this->name . "'");
            }
            $this->execute('vacuum');

            return;
        }

        if ($driver_name == 'pgsql') {
            $query = 'TRUNCATE TABLE ' . $this->quote_identifier($this->name);

            if ($reset_autoincrement) {
                $query .= ' RESTART IDENTITY';
            }

            $this->execute($query);

            return;
        }

        $this->execute("TRUNCATE TABLE " . $this->quote_identifier($this->name));
        $this->execute("ALTER TABLE " . $this->quote_identifier($this->name) . " AUTO_INCREMENT = 1");
    }

    /**
     * Drops table.
     *
     * @throws StatementNotValid when the table cannot be dropped.
     */
    public function drop(bool $if_exists = false): void
    {
        $query = 'DROP TABLE' . ($if_exists ? ' IF EXISTS ' : '') . ' ' . $this->quote_identifier($this->name);

        $this->execute($query);
    }
}
