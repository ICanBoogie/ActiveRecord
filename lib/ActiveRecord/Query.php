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

use ArrayIterator;
use DateTimeInterface;
use ICanBoogie\ActiveRecord;
use ICanBoogie\DateTime;
use ICanBoogie\Prototype\MethodNotDefined;
use ICanBoogie\PrototypeTrait;
use InvalidArgumentException;
use IteratorAggregate;
use JetBrains\PhpStorm\Deprecated;
use LogicException;
use PDO;
use ReflectionClass;
use ReflectionMethod;
use Traversable;

use function array_combine;
use function array_fill;
use function array_map;
use function array_merge;
use function array_shift;
use function count;
use function explode;
use function func_get_arg;
use function func_get_args;
use function func_num_args;
use function get_class;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function preg_replace_callback;
use function reset;
use function strpos;
use function substr;

use const PHP_INT_MAX;

/**
 * The class offers many features to compose model queries. Most query related
 * methods of the {@link Model} class create a {@link Query} object that is returned for
 * further specification, such as filters or limits.
 *
 * @method Query and ($conditions, $conditions_args = null, $_ = null) Alias to where().
 *
 * @property-read array $all An array with all the records matching the query.
 * @property-read mixed $one The first record matching the query.
 * @property-read array $pairs An array of key/value pairs.
 * @property-read array $rc The first column of the first row matching the query.
 * @property-read int $count The number of records matching the query.
 * @property-read bool|array $exists `true` if a record matching the query exists, `false`
 * otherwise. If there is multiple records, the property is an array of booleans.
 *
 * @property-read Model $model The target model of the query.
 * @property-read array $joints The joints collection from {@link join()}.
 * @property-read array $joints_args The arguments to the joints.
 * @property-read array $conditions The collected conditions.
 * @property-read array $conditions_args The arguments to the conditions.
 * @property-read array $having_args The arguments to the `HAVING` clause.
 * @property-read array $args Returns the arguments to the query.
 * @property-read Query $prepared Return a prepared query.
 *
 * @template TRecord of ActiveRecord
 *
 * @implements IteratorAggregate<TRecord>
 */
class Query implements IteratorAggregate
{
    use PrototypeTrait {
        PrototypeTrait::__call as private __prototype_call;
    }

    public const LIMIT_MAX = PHP_INT_MAX;

    /**
     * Part of the `SELECT` clause.
     *
     * @var string
     */
    private $select;

    /**
     * `JOIN` clauses.
     *
     * @var array
     * @uses get_joints
     */
    private $joints = [];

    private function get_joints(): array
    {
        return $this->joints;
    }

    /**
     * Joints arguments.
     *
     * @var array
     * @uses get_joints_args
     * @uses get_args
     */
    private $joints_args = [];

    private function get_joints_args(): array
    {
        return $this->joints_args;
    }

    /**
     * Collected conditions.
     *
     * @var array
     * @uses get_conditions
     */
    private $conditions = [];

    private function get_conditions(): array
    {
        return $this->conditions;
    }

    /**
     * Arguments for the conditions.
     *
     * @var array
     * @uses get_conditions_args
     * @uses get_args
     */
    private $conditions_args = [];

    private function get_conditions_args(): array
    {
        return $this->conditions_args;
    }

    /**
     * Part of the `HAVING` clause.
     *
     * @var string
     */
    private $having;

    /**
     * Arguments to the `HAVING` clause.
     *
     * @var array
     * @uses get_having_args
     * @uses get_args
     */
    private $having_args = [];

    private function get_having_args(): array
    {
        return $this->having_args;
    }

    /**
     * Returns the arguments to the query, which include joints arguments, conditions arguments,
     * and _having_ arguments.
     *
     * @return array
     */
    private function get_args(): array
    {
        return array_merge($this->joints_args, $this->conditions_args, $this->having_args);
    }

    /**
     * Part of the `GROUP BY` clause.
     *
     * @var string
     */
    private $group;

    /**
     * Part of the `ORDER BY` clause.
     *
     * @var mixed
     */
    private $order;

    /**
     * The number of records the skip before fetching.
     *
     * @var int
     */
    private $offset;

    /**
     * The maximum number of records to fetch.
     *
     * @var int
     */
    private $limit;

    /**
     * Fetch mode.
     *
     * @var mixed
     */
    private $mode;

    /**
     * The target model of the query.
     *
     * @var Model
     * @uses get_model
     */
    private $model;

    private function get_model(): Model
    {
        return $this->model;
    }

    /**
     * @param Model $model The model to query.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Override the method to handle magic 'filter_by_' methods.
     *
     * @inheritdoc
     */
    public function __call($method, $arguments)
    {
        if ($method === 'and') {
            return $this->where(...$arguments);
        }

        if (strpos($method, 'filter_by_') === 0) {
            return $this->dynamic_filter(substr($method, 10), $arguments); // 10 is for: strlen('filter_by_')
        }

        try {
            return self::__prototype_call($method, $arguments);
        } catch (MethodNotDefined $e) {
            throw new ScopeNotDefined($method, $this->model, $e);
        }
    }

    /*
     * Rendering
     */

    /**
     * Convert the query into a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->resolve_statement(
            $this->render_select() . ' ' .
            $this->render_from() .
            $this->render_main()
        );
    }

    /**
     * Render the `SELECT` clause.
     *
     * @return string
     */
    private function render_select(): string
    {
        return 'SELECT ' . ($this->select ? $this->select : '*');
    }

    /**
     * Render the `FROM` clause.
     *
     * The rendered `FROM` clause might include some JOINS too.
     *
     * @return string
     */
    private function render_from(): string
    {
        return 'FROM {self_and_related}';
    }

    /**
     * Renders the `JOIN` clauses.
     *
     * @return string
     */
    private function render_joints(): string
    {
        return implode(' ', $this->joints);
    }

    /**
     * Render the main body of the query, without the `SELECT` and `FROM` clauses.
     *
     * @return string
     */
    private function render_main(): string
    {
        $query = '';

        if ($this->joints) {
            $query = ' ' . $this->render_joints();
        }

        $conditions = $this->conditions;

        if ($conditions) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $group = $this->group;

        if ($group) {
            $query .= ' GROUP BY ' . $group;

            $having = $this->having;

            if ($having) {
                $query .= ' HAVING ' . $having;
            }
        }

        $order = $this->order;

        if ($order) {
            $query .= ' ' . $this->render_order($order);
        }

        $offset = $this->offset;
        $limit = $this->limit;

        if ($offset || $limit) {
            $query .= ' ' . $this->render_offset_and_limit($offset, $limit);
        }

        return $query;
    }

    /**
     * Render the `ORDER` clause.
     */
    private function render_order(array $order): string
    {
        if (count($order) == 1) {
            $raw = $order[0];
            $rendered = preg_replace(
                '/-([a-zA-Z0-9_]+)/',
                '$1 DESC',
                $raw
            );

            return 'ORDER BY ' . $rendered;
        }

        $connection = $this->model->connection;

        $field = array_shift($order);
        $field_values = is_array($order[0]) ? $order[0] : $order;
        $field_values = array_map(function ($v) use ($connection) {
            return $connection->pdo->quote($v);
        }, $field_values);

        return "ORDER BY FIELD($field, " . implode(', ', $field_values) . ")";
    }

    /**
     * Render the `LIMIT` and `OFFSET` clauses.
     *
     * @param int $offset
     * @param int $limit
     *
     * @return string
     */
    private function render_offset_and_limit($offset, $limit): string
    {
        if ($offset && $limit) {
            return "LIMIT $offset, $limit";
        } else {
            if ($offset) {
                return "LIMIT $offset, " . self::LIMIT_MAX;
            } else {
                if ($limit) {
                    return "LIMIT $limit";
                }
            }
        }

        return '';
    }

    /*
     *
     */

    /**
     * Resolve the placeholders of a statement.
     *
     * Note: Currently, the method simply forwards the statement to the model's
     * resolve_statement() method.
     *
     * @param string $statement
     *
     * @return string
     */
    private function resolve_statement(string $statement): string
    {
        return $this->model->resolve_statement($statement);
    }

    /**
     * Define the `SELECT` clause.
     *
     * @param string $expression The expression of the `SELECT` clause. e.g. 'nid, title'.
     *
     * @return $this
     */
    public function select($expression): self
    {
        $this->select = $expression;

        return $this;
    }

    /**
     * Add a `JOIN` clause.
     *
     * @param ?string $expression
     *     A raw `JOIN` clause.
     * @param ?Query<ActiveRecord> $query
     *     A {@link Query} instance, it is rendered as a string and used as a subquery of the `JOIN` clause.
     *     The `$options` parameter can be used to customize the output.
     * @param ?class-string<ActiveRecord> $with
     * @param ?class-string<Model> $model_class
     *     A model class.
     * @param ?Model $model
     *     A model.
     * @param ?non-empty-string $mode
     *     Join mode. Default: "INNER"
     * @param ?non-empty-string $as
     *     The alias of the subquery. Default: The query's model alias.
     * @param ?non-empty-string $on
     *     The column on which to joint is created. Default: The query's model primary key.
     *
     * @return $this
     *
     * <pre>
     * <?php
     *
     * # using a model identifier
     *
     * $query->join(model_id: 'nodes');
     *
     * # using a subquery
     *
     * $subquery = get_model('updates')
     * ->select('updated_at, $subscriber_id, update_hash')
     * ->order('updated_at DESC')
     *
     * $query->join(query: $subquery, on: 'subscriber_id');
     *
     * # using a raw clause
     *
     * $query->join(expression: "INNER JOIN `articles` USING(`nid`)");
     * </pre>
     */
    public function join(
        string $expression = null,
        Query $query = null,
        string $with = null,
        string $mode = 'INNER',
        string $as = null,
        string $on = null,
    ): self {
        if ($expression) {
            $this->joints[] = $expression;

            return $this;
        }

        if ($query) {
            $this->join_with_query($query, mode: $mode, as: $as, on: $on);

            return $this;
        }

        if ($with) {
            $model = $this->model->models->model_for_record($with);

            $this->join_with_model($model, mode: $mode, as: $as, on: $on);

            return $this;
        }

        throw new LogicException("One of [ expression, query, record ] needs to be defined");
    }

    /**
     * Join a subquery to the query.
     *
     * @param Query<ActiveRecord> $query
     * @param string $mode
     *     Join mode. Default: "INNER".
     * @param ?string $as
     *     The alias of the subquery. Default: The query's model alias.
     * @param ?string $on
     *     The column on which the joint is created. Default: The query's model primary key.
     */
    private function join_with_query(
        Query $query,
        string $mode = 'INNER',
        string $as = null,
        string $on = null,
    ): void {
        $as ??= $query->model->alias;
        $on ??= $query->model->primary;

        if ($on) {
            assert(is_string($on));

            $on = $this->render_join_on($on, $as, $query);
        }

        if ($on) {
            $on = ' ' . $on;
        }

        $this->joints[] = "$mode JOIN($query) `$as`{$on}";
        $this->joints_args = array_merge($this->joints_args, $query->args);
    }

    /**
     * Join a model to the query.
     *
     * @param Model<int|string|string[], ActiveRecord> $model
     * @param non-empty-string $mode
     *     Join mode.
     * @param ?non-empty-string $as
     *     The alias of the model. Default: The model's alias.
     * @param ?non-empty-string $on
     *     The column on which the joint is created, or an _ON_ expression. Default: The model's primary key. @todo
     */
    private function join_with_model( // @phpstan-ignore-line
        Model $model,
        string $mode = 'INNER',
        string $as = null,
        string $on = null,
    ): void {
        $as ??= $model->alias;
        //phpcs:disable PSR2.Methods.FunctionCallSignature.SpaceBeforeOpenBracket
        $on ??= (function () use ($model): string {
            $primary = $this->model->primary;
            $model_schema = $model->extended_schema;

            assert(is_array($primary) || is_string($primary));

            if (is_array($primary)) {
                foreach ($primary as $column) {
                    if ($model_schema->has_column($column)) {
                        return $column;
                    }
                }
            } elseif (!$model_schema->has_column($primary)) {
                $primary = $model_schema->primary;

                if (is_array($primary)) {
                    $primary = reset($primary);
                }
            }

            assert(is_string($primary));

            return $primary;
        }) ();

        $this->joints[] = "$mode JOIN `$model->name` AS `$as` USING(`$on`)";
    }

    /**
     * Render the `on` join option.
     *
     * The method tries to determine the best solution between `ON` and `USING`.
     *
     * @param string $column
     * @param string $as
     * @param Query<ActiveRecord> $query
     */
    private function render_join_on(string $column, string $as, Query $query): string
    {
        if ($query->model->schema->has_column($column) && $this->model->schema->has_column($column)) {
            return "USING(`$column`)";
        }

        $target = $this->model;

        while ($target) {
            if ($target->schema->has_column($column)) {
                break;
            }

            $target = $target->parent_model;
        }

        if (!$target) {
            $model_class = $this->model::class;

            throw new InvalidArgumentException("Unable to resolve column `$column` from model {$model_class}");
        }

        return "ON `$as`.`$column` = `{$target->alias}`.`$column`";
    }

    /**
     * Parse the conditions for the {@link where()} and {@link having()} methods.
     *
     * {@link DateTimeInterface} conditions are converted to strings.
     *
     * @param $conditions_and_args
     *
     * @return array An array made of the condition string and its arguments.
     */
    private function deferred_parse_conditions(...$conditions_and_args): array
    {
        $conditions = array_shift($conditions_and_args);
        $args = $conditions_and_args;

        if (is_array($conditions)) {
            $c = '';
            $conditions_args = [];

            foreach ($conditions as $column => $arg) {
                if (is_array($arg) || $arg instanceof self) {
                    $joined = '';

                    if (is_array($arg)) {
                        foreach ($arg as $value) {
                            $joined .= ',' . (is_numeric($value) ? $value : $this->model->quote($value));
                        }

                        $joined = substr($joined, 1);
                    } else {
                        $joined = (string)$arg;
                        $conditions_args = array_merge($conditions_args, $arg->args);
                    }

                    $c .= ' AND `' . ($column[0] == '!' ? substr($column, 1) . '` NOT' : $column . '`')
                        . ' IN(' . $joined . ')';
                } else {
                    $conditions_args[] = $arg;

                    $c .= ' AND `' . ($column[0] == '!' ? substr($column, 1) . '` !' : $column . '` ')
                        . '= ?';
                }
            }

            $conditions = substr($c, 5);
        } else {
            $conditions_args = [];

            if ($args) {
                if (is_array($args[0])) {
                    $conditions_args = $args[0];
                } else {
                    #
                    # We dereference values otherwise the caller would get a corrupted array.
                    #

                    foreach ($args as $key => $value) {
                        $conditions_args[$key] = $value;
                    }
                }
            }
        }

        foreach ($conditions_args as &$value) {
            if ($value instanceof DateTimeInterface) {
                $value = DateTime::from($value)->utc->as_db;
            }
        }

        return [ $conditions ? '(' . $conditions . ')' : null, $conditions_args ];
    }

    /**
     * Handles dynamic filters.
     *
     * @param string $filter
     * @param array $conditions_args
     *
     * @return $this
     */
    private function dynamic_filter(string $filter, array $conditions_args = []): self
    {
        $conditions = explode('_and_', $filter);

        return $this->where(array_combine($conditions, $conditions_args));
    }

    /**
     * Add conditions to the SQL statement.
     *
     * Conditions can either be specified as string or array.
     *
     * 1. Pure string conditions
     *
     * If you'de like to add conditions to your statement, you could just specify them in there,
     * just like `$model->where('order_count = 2');`. This will find all the entries, where the
     * `order_count` field's value is 2.
     *
     * 2. Array conditions
     *
     * Now what if that number could vary, say as an argument from somewhere, or perhaps from the
     * user’s level status somewhere? The find then becomes something like:
     *
     * `$model->where('order_count = ?', 2);`
     *
     * or
     *
     * `$model->where([ 'order_count' => 2 ]);`
     *
     * Or if you want to specify two conditions, you can do it like:
     *
     * `$model->where('order_count = ? AND locked = ?', 2, false);`
     *
     * or
     *
     * `$model->where([ 'order_count' => 2, 'locked' => false ]);`
     *
     * Or if you want to specify subset conditions:
     *
     * `$model->where([ 'order_id' => [ 123, 456, 789 ] ]);`
     *
     * This will return the orders with the `order_id` 123, 456 or 789.
     *
     * 3. Modifiers
     *
     * When using the "identifier" => "value" notation, you can switch the comparison method by
     * prefixing the identifier with a bang "!"
     *
     * `$model->where([ '!order_id' => [ 123, 456, 789 ]]);`
     *
     * This will return the orders with the `order_id` different than 123, 456 and 789.
     *
     * `$model->where([ '!order_count' => 2 ];`
     *
     * This will return the orders with the `order_count` different than 2.
     *
     * @param mixed ...$conditions_and_args
     *
     * @return $this
     */
    public function where(...$conditions_and_args): self
    {
        [ $conditions, $conditions_args ] = $this->deferred_parse_conditions(...$conditions_and_args);

        if ($conditions) {
            $this->conditions[] = $conditions;

            if ($conditions_args) {
                $this->conditions_args = array_merge($this->conditions_args, $conditions_args);
            }
        }

        return $this;
    }

    /**
     * Defines the `ORDER` clause.
     *
     * @param string $order_or_field_name The order for the `ORDER` clause e.g.
     * 'weight, date DESC', or field to order with, in which case `$field_values` is required.
     * @param array $field_values Values of the field specified by `$order_or_field_name`.
     *
     * @return $this
     */
    public function order($order_or_field_name, $field_values = null)
    {
        $this->order = func_get_args();

        return $this;
    }

    /**
     * Defines the `GROUP` clause.
     *
     * @param string $group
     *
     * @return $this
     */
    public function group($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Defines the `HAVING` clause.
     *
     * @param mixed ...$conditions_and_args
     *
     * @return $this
     */
    public function having(...$conditions_and_args)
    {
        list($having, $having_args) = $this->deferred_parse_conditions(...$conditions_and_args);

        $this->having = $having;
        $this->having_args = $having_args;

        return $this;
    }

    /**
     * Define the offset of the `LIMIT` clause.
     *
     * @param $offset
     *
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;

        return $this;
    }

    /**
     * Apply the limit and/or offset to the SQL fired.
     *
     * You can use the limit to specify the number of records to be retrieved, ad use the offset to
     * specify the number of records to skip before starting to return records:
     *
     *     $model->limit(10);
     *
     * Will return a maximum of 10 clients and because ti specifies no offset it will return the
     * first 10 in the table:
     *
     *     $model->limit(5, 10);
     *
     * Will return a maximum of 10 clients beginning with the 5th.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $offset = null;

        if (func_num_args() == 2) {
            $offset = $limit;
            $limit = func_get_arg(1);
        }

        $this->offset = (int)$offset;
        $this->limit = (int)$limit;

        return $this;
    }

    /**
     * Set the fetch mode for the query.
     *
     * @param mixed ...$mode
     *
     * @return $this
     *
     * @see http://www.php.net/manual/en/pdostatement.setfetchmode.php
     */
    public function mode(...$mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Prepare the query.
     *
     * We use the connection's prepare() method because the statement has already been resolved
     * during the __toString() method and we don't want for the statement to be parsed twice.
     *
     * @return Statement
     */
    private function prepare(): Statement
    {
        return $this->model->connection->prepare((string)$this);
    }

    /**
     * Return a prepared query.
     *
     * @return Statement
     */
    protected function get_prepared(): Statement
    {
        return $this->prepare();
    }

    /**
     * Prepare and executes the query.
     *
     * @return Statement
     */
    public function query(): Statement
    {
        $statement = $this->prepare();
        $statement->execute($this->args);

        return $statement;
    }

    /*
     * FINISHER
     */

    /**
     * Resolves fetch mode.
     *
     * @param mixed ...$mode
     *
     * @return array<mixed>
     */
    private function resolve_fetch_mode(...$mode): array
    {
        if ($mode) {
            $args = $mode;
        } elseif ($this->mode) {
            $args = $this->mode;
        } elseif ($this->select) {
            $args = [ PDO::FETCH_ASSOC ];
        } elseif ($this->model->activerecord_class) {
            $args = [ PDO::FETCH_CLASS, $this->model->activerecord_class, [ $this->model ] ];
        } else {
            $args = [ PDO::FETCH_CLASS, ActiveRecord::class, [ $this->model ] ];
        }

        return $args;
    }

    /**
     * Execute the query and returns an array of records.
     *
     * @param mixed ...$mode Fetch mode.
     *
     * @return array<mixed>
     */
    public function all(...$mode): array
    {
        return $this->query()->pdo_statement->fetchAll(...$this->resolve_fetch_mode(...$mode));
    }

    /**
     * Getter for the {@link $all} magic property.
     *
     * @return array<mixed>
     */
    protected function get_all(): array
    {
        return $this->all();
    }

    /**
     * Return the first result of the query and close the cursor.
     *
     * @param mixed ...$mode Fetch node.
     *
     * @return mixed The return value of this function on success depends on the fetch mode. In
     * all cases, FALSE is returned on failure.
     */
    public function one(...$mode)
    {
        $query = clone $this;
        $query->limit = 1;
        $statement = $query->query();
        $args = $query->resolve_fetch_mode(...$mode);

        if (count($args) > 1 && $args[0] == PDO::FETCH_CLASS) {
            array_shift($args);

            $rc = $statement->pdo_statement->fetchObject(...$args);

            $statement->pdo_statement->closeCursor();

            return $rc;
        }

        return $statement->one(...$args);
    }

    /**
     * Getter for the {@link $one} magic property.
     *
     * @return mixed
     *
     * @see one()
     */
    protected function get_one()
    {
        return $this->one();
    }

    /**
     * Execute que query and return an array of key/value pairs, where the key is the value of
     * the first column and the value of the key the value of the second column.
     *
     * @return array
     */
    protected function get_pairs(): array
    {
        return $this->all(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Return the value of the first column of the first row.
     *
     * @return mixed
     */
    protected function get_rc()
    {
        $previous_limit = $this->limit;

        $this->limit = 1;

        $statement = $this->query();

        $this->limit = $previous_limit;

        return $statement->rc;
    }

    /**
     * Check the existence of records in the model.
     *
     * $model->exists;
     * $model->where('name = "max"')->exists;
     * $model->exists(1);
     * $model->exists(1, 2);
     * $model->exists([ 1, 2 ]);
     *
     * @param mixed $key
     *
     * @return bool|array
     */
    public function exists($key = null)
    {
        if ($key !== null && func_num_args() > 1) {
            $key = func_get_args();
        }

        $query = clone $this;

        #
        # Checking if the query matches any record.
        #

        if ($key === null) {
            return !!$query
                ->select('1')
                ->limit(1)
                ->rc;
        }

        #
        # Checking if the query matches the specified record keys.
        #

        $rc = $query
            ->select('`{primary}`')
            ->and([ '{primary}' => $key ])
            ->limit(0, 0)
            ->all(PDO::FETCH_COLUMN);

        if ($rc && is_array($key)) {
            $exists = array_combine($key, array_fill(0, count($key), false));

            foreach ($rc as $key) {
                $exists[$key] = true;
            }

            foreach ($exists as $v) {
                if (!$v) {
                    return $exists;
                }
            }

            # all true

            return true;
        }

        return !empty($rc);
    }

    /**
     * Getter for the {@link $exists} magic property.
     *
     * @return bool|array
     *
     * @see exists()
     */
    protected function get_exists()
    {
        return $this->exists();
    }

    /**
     * Handle all the computations.
     *
     * @param string $method
     * @param string|null $column
     *
     * @return int|array
     */
    private function compute(string $method, string $column = null)
    {
        $query = 'SELECT ';

        if ($column) {
            if ($method == 'COUNT') {
                $query .= "`$column`, $method(`$column`)";

                $this->group($column);
            } else {
                $query .= "$method(`$column`)";
            }
        } else {
            $query .= $method . '(*)';
        }

        $query .= ' AS count ' . $this->render_from() . $this->render_main();
        $statement = ($this->model)($query, $this->args);

        if ($method == 'COUNT' && $column) {
            return $statement->pairs;
        }

        return (int)$statement->rc;
    }

    /**
     * Implement the 'COUNT' computation.
     *
     * @param string|null $column The name of the column to count.
     */
    public function count(string $column = null): int|array
    {
        return $this->compute('COUNT', $column);
    }

    /**
     * Getter for the {@link $count} magic property.
     *
     * @return int
     */
    protected function get_count(): int
    {
        return $this->count();
    }

    /**
     * Implement the 'AVG' computation.
     *
     * @param string $column
     *
     * @return int
     */
    public function average(string $column)
    {
        return $this->compute('AVG', $column);
    }

    /**
     * Implement the 'MIN' computation.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function minimum(string $column)
    {
        return $this->compute('MIN', $column);
    }

    /**
     * Implement the 'MAX' computation.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function maximum(string $column)
    {
        return $this->compute('MAX', $column);
    }

    /**
     * Implement the 'SUM' computation.
     *
     * @param string $column
     *
     * @return mixed
     */
    public function sum(string $column)
    {
        return $this->compute('SUM', $column);
    }

    /**
     * Delete the records matching the conditions and limits of the query.
     *
     * @param string $tables When using a JOIN, `$tables` is used to specify the tables in which
     * records should be deleted. Default: The alias of queried model, only if at least one join
     * clause has been defined using the {@link join()} method.
     *
     * @return bool The result of the operation.
     *
     * @todo-20140901: reflect on join to add the required tables by default, discarding tables
     * joined with the LEFT mode.
     */
    public function delete($tables = null)
    {
        if (!$tables && $this->joints) {
            $tables = "`{alias}`";
        }

        if ($tables) {
            $query = "DELETE {$tables} FROM {self} AS `{alias}`";
        } else {
            $query = "DELETE FROM {self}";
        }

        $query .= $this->render_main();

        return $this->model->execute($query, $this->args);
    }

    /**
     * Return an iterator for the query.
     *
     * @return Traversable<TRecord>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
}
