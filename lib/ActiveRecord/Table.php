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

use AllowDynamicProperties;
use ICanBoogie\ActiveRecord\Config\TableDefinition;
use ICanBoogie\Prototyped;
use InvalidArgumentException;
use LogicException;
use PDO;
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
use function is_numeric;
use function is_string;
use function strtr;

/**
 * A representation of a database table.
 *
 * @property-read Schema $extended_schema The extended schema of the table.
 */
#[AllowDynamicProperties]
class Table extends Prototyped
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
     * The "{primary}" placeholder used in queries is replaced by the properties value.
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
     *
     * @var string
     */
    protected $update_join;

    protected function lazy_get_update_join(): string
    {
        $join = '';
        $parent = $this->parent;

        while ($parent) {
            $join .= " INNER JOIN `{$parent->name}` `{$parent->alias}` USING(`{$this->primary}`)";
            $parent = $parent->parent;
        }

        return $join;
    }

    /**
     * SQL fragment for the FROM clause of the query, made of the table's name and alias and those
     * of the related tables, inherited and implemented.
     *
     * The "{self_and_related}" placeholder used in queries is replaced by the properties value.
     *
     * @var string
     */
    protected $select_join;

    protected function lazy_get_select_join(): string
    {
        return "`{$this->alias}`" . $this->update_join;
    }

    /**
     * Returns the extended schema.
     */
    protected function lazy_get_extended_schema(): Schema
    {
        $table = $this;
        $columns = [];

        while ($table) {
            $columns[] = $table->schema->columns;

            $table = $table->parent;
        }

        $columns = array_reverse($columns);
        $columns = array_merge(...array_values($columns));

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

        unset($this->update_join);
        unset($this->select_join);
}

    /**
     * Interface to the connection's query() method.
     *
     * The statement is resolved using the resolve_statement() method and prepared.
     *
     * @param non-empty-string $query
     * @param array<mixed> $args
     * @param array<non-empty-string, mixed> $options
     */
    public function __invoke(string $query, array $args = [], array $options = []): Statement
    {
        $statement = $this->prepare($query, $options);

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
     * The following placeholder are replaced:
     *
     * - `{alias}`: The alias of the table.
     * - `{prefix}`: The prefix used for the tables of the connection.
     * - `{primary}`: The primary key of the table.
     * - `{self}`: The name of the table.
     * - `{self_and_related}`: The escaped name of the table and the possible JOIN clauses.
     *
     * Note: If the table has a multi-column primary keys `{primary}` is replaced by
     * `__multicolumn_primary__<concatenated_columns>` where `<concatenated_columns>` is a the columns
     * concatenated with an underscore ("_") as separator. For instance, if a table primary key is
     * made of columns "p1" and "p2", `{primary}` is replaced by `__multicolumn_primary__p1_p2`.
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
     * The statement is resolved by the {@link resolve_statement()} method before the call is
     * forwarded.
     *
     * @param array<string, mixed> $options
     */
    public function prepare(string $query, array $options = []): Statement
    {
        $query = $this->resolve_statement($query);

        return $this->connection->prepare($query, $options);
    }

    /**
     * @see PDO::quote()
     */
    public function quote(string $string, int $type = PDO::PARAM_STR): string
    {
        $quoted = $this->connection->pdo->quote($string, $type);

        if ($quoted === false) {
            throw new InvalidArgumentException("Unsupported quote type: $type");
        }

        return $quoted;
    }

    /**
     * Executes a statement.
     *
     * The statement is prepared by the {@link prepare()} method before it is executed.
     *
     * @param non-empty-string $query
     * @param array<int|string, mixed> $args
     * @param array<string, mixed> $options
     */
    public function execute(string $query, array $args = [], array $options = []): Statement
    {
        $statement = $this->prepare($query, $options);

        return $statement($args);
    }

    /**
     * Filters mass assignment values.
     *
     * @param array<non-empty-string, mixed> $values
     *
     * @return array{ mixed[], array<non-empty-string, non-empty-string>, non-empty-string[] }
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
    public function save(array $values, mixed $id = null, array $options = []): mixed
    {
        if ($id) {
            return $this->update($values, $id) ? $id : false;
        }

        return $this->save_callback($values, $id, $options);
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $options
     *
     * @return bool|int|null|string
     */
    private function save_callback(array $values, mixed $id = null, array $options = []): mixed
    {
        if ($id) {
            $this->update($values, $id);

            return $id;
        }

        $parent_id = 0;

        if ($this->parent) {
            $parent_id = $this->parent->save_callback($values, null, $options)
                ?: throw new \Exception("Parent save failed: {$this->parent->name} returning {$parent_id}.");

            assert(is_string($this->primary));
            assert(is_numeric($parent_id));

            $values[$this->primary] = $parent_id;
        }

        $driver_name = $this->connection->driver_name;

        [ $filtered, $holders, $identifiers ] = $this->filter_values($values);

        // FIXME: ALL THIS NEED REWRITE !

        if ($holders) {
            // faire attention à l'id, si l'on revient du parent qui a inséré, on doit insérer aussi, avec son id

            if ($driver_name === 'mysql') {
//                if ($parent_id && empty($holders[$this->primary])) {
//                    $filtered[] = $parent_id;
//                    $holders[] = '`{primary}` = ?';
//                }

                $statement = 'INSERT INTO `{self}` SET ' . implode(', ', $holders);
                $statement = $this->prepare($statement);

                $rc = $statement->execute($filtered);
            } elseif ($driver_name === 'sqlite') {
                $rc = $this->insert($values, $options);
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

            $rc = $statement->execute($filtered);
        } else {
            $rc = true;
        }

        if ($parent_id) {
            return $parent_id;
        }

        if (!$rc) {
            return false;
        }

        return $this->connection->pdo->lastInsertId();
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
        [ $values, $holders, $identifiers ] = $this->filter_values($values);

        if (!$values) {
            return null;
        }

        $driver_name = $this->connection->driver_name;

        $on_duplicate = $options['on duplicate'] ?? null;

        if ($driver_name == 'mysql') {
            $query = 'INSERT';

            if (!empty($options['ignore'])) {
                $query .= ' IGNORE ';
            }

            $query .= ' INTO `{self}` SET ' . implode(', ', $holders);

            if ($on_duplicate) {
                if ($on_duplicate === true) {
                    #
                    # if 'on duplicate' is true, we use the same input values, but we take care of
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
                } else {
                    [ $update_values, $update_holders ] = $this->filter_values($on_duplicate);
                }

                $query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $update_holders);

                $values = array_merge($values, $update_values);
            }
        } elseif ($driver_name == 'sqlite') {
            $holders = array_fill(0, count($identifiers), '?');

            $query = 'INSERT' . ($on_duplicate ? ' OR REPLACE' : '')
                . ' INTO `{self}` (' . implode(', ', $identifiers) . ')'
                . ' VALUES (' . implode(', ', $holders) . ')';
        } else {
            throw new LogicException("Unsupported drive: $driver_name.");
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

        if ($this->connection->driver_name == 'sqlite') {
            $table = $this;
            $rc = true;

            while ($table) {
                [ $table_values, $holders ] = $table->filter_values($values);

                if ($holders) {
                    $query = 'UPDATE `{self}` SET ' . implode(', ', $holders) . ' WHERE `{primary}` = ?';
                    $table_values[] = $key;

                    $rc = $table->execute($query, $table_values);

                    if (!$rc) {
                        return $rc;
                    }
                }

                $table = $table->parent;
            }

            return $rc;
        }

        [ $values, $holders ] = $this->filter_values($values, true);

        $query = "UPDATE `{self}` $this->update_join  SET " . implode(', ', $holders) . ' WHERE `{primary}` = ?';
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
        if ($this->parent) {
            $this->parent->delete($key);
        }

        $where = 'where ';

        if (is_array($this->primary)) {
            $parts = [];

            foreach ($this->primary as $identifier) {
                $parts[] = '`' . $identifier . '` = ?';
            }

            $where .= implode(' and ', $parts);
        } else {
            $where .= '`{primary}` = ?';
        }

        $statement = $this->prepare('DELETE FROM `{self}` ' . $where);
        $statement((array)$key);

        return !!$statement->pdo_statement->rowCount();
    }

    /**
     * Truncates table.
     *
     * @return mixed
     *
     * @FIXME-20081223: what about extends ?
     */
    public function truncate()
    {
        if ($this->connection->driver_name == 'sqlite') {
            $rc = $this->execute('DELETE FROM `{self}`');

            $this->execute('vacuum');

            return $rc;
        }

        return $this->execute('TRUNCATE TABLE `{self}`');
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
