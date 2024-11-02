<?php

namespace ICanBoogie\ActiveRecord;

use DateTimeInterface;
use ICanBoogie\ActiveRecord;
use ICanBoogie\PrototypeTrait;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use PDO;
use Traversable;

use function array_map;
use function array_merge;
use function array_shift;
use function count;
use function func_get_args;
use function func_num_args;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function reset;
use function substr;

use const PHP_INT_MAX;

/**
 * @template TRecord of ActiveRecord
 *
 * @implements IteratorAggregate<TRecord>
 *
 * The class offers many features to compose model queries. Most query-related
 * methods of the {@see Model} class create a {@see Query} object returned for
 * further specification, such as filters or limits.
 *
 * @see self::get_all()
 * @property-read array $all An array with all the records matching the query.
 * @see self::get_one()
 * @property-read TRecord|array<string>|false $one The first record matching the query.
 * @see self::get_pairs()
 * @property-read array $pairs An array of key/value pairs.
 * @see self::get_rc()
 * @property-read int|string|false|null $rc The first column of the first row matching the query.
 * @see self::get_count()
 * @property-read int $count The number of records matching the query.
 * @see self::get_exists()
 * @property-read bool|array $exists `true` if a record matching the query exists, `false`
 * otherwise. If there are multiple records, the property is an array of booleans.
 *
 * @see self::get_joins()
 * @property-read non-empty-string[] $joins The join collection from {@see join()}.
 * @see self::get_joins_args()
 * @property-read mixed[] $joins_args The arguments to the joins.
 * @see self::get_conditions()
 * @property-read non-empty-string[] $conditions The collected conditions.
 * @see self::get_conditions_args()
 * @property-read mixed[] $conditions_args The arguments to the conditions.
 * @see self::get_having_args()
 * @property-read mixed[] $having_args The arguments to the `HAVING` clause.
 * @see self::get_args()
 * @property-read mixed[] $args Returns the arguments to the query.
 * @see self::get_prepared()
 * @property-read Query<TRecord> $prepared Return a prepared query.
 */
class Query implements IteratorAggregate
{
    use PrototypeTrait;

    public const LIMIT_MAX = PHP_INT_MAX;

    /**
     * Part of the `SELECT` clause.
     */
    private ?string $select = null;

    /**
     * `JOIN` clauses.
     *
     * @var non-empty-string[]
     */
    private array $joins = [];

    /**
     * @return non-empty-string[]
     *
     * @see $joins
     */
    private function get_joins(): array
    {
        return $this->joins;
    }

    /**
     * Joints arguments.
     *
     * @var mixed[]
     */
    private array $joins_args = [];

    /**
     * @return mixed[]
     *
     * @see $joins_args
     */
    private function get_joins_args(): array
    {
        return $this->joins_args;
    }

    /**
     * Collected conditions.
     *
     * @var non-empty-string[]
     */
    private array $conditions = [];

    /**
     * @return non-empty-string[]
     */
    private function get_conditions(): array
    {
        return $this->conditions;
    }

    /**
     * Arguments for the conditions.
     *
     * @var mixed[]
     */
    private array $conditions_args = [];

    /**
     * @return mixed[]
     */
    private function get_conditions_args(): array
    {
        return $this->conditions_args;
    }

    /**
     * Part of the `HAVING` clause.
     */
    private ?string $having = null;

    /**
     * Arguments to the `HAVING` clause.
     *
     * @var mixed[]
     */
    private array $having_args = [];

    /**
     * @return mixed[]
     */
    private function get_having_args(): array
    {
        return $this->having_args;
    }

    /**
     * Returns the arguments to the query, which include joins arguments, conditions arguments,
     * and _having_ arguments.
     *
     * @return mixed[]
     */
    private function get_args(): array
    {
        return array_merge($this->joins_args, $this->conditions_args, $this->having_args);
    }

    /**
     * Part of the `GROUP BY` clause.
     */
    private ?string $group = null;

    /**
     * Part of the `ORDER BY` clause.
     *
     * @var mixed[]
     */
    private array $order = [];

    /**
     * The number of records the skip before fetching.
     */
    private ?int $skip = null;

    /**
     * The maximum number of records to take when fetching.
     */
    private ?int $take = null;

    /**
     * Fetch mode.
     *
     * @var mixed[]
     */
    private array $mode = [];

    /**
     * @param Model<int|non-empty-string, TRecord> $model The model to query.
     */
    public function __construct(
        public readonly Model $model
    ) {
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
        return 'SELECT ' . ($this->select ?? '*');
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
    private function render_joins(): string
    {
        return implode(' ', $this->joins);
    }

    /**
     * Render the main body of the query, without the `SELECT` and `FROM` clauses.
     */
    private function render_main(): string
    {
        $query = '';

        if ($this->joins) {
            $query = ' ' . $this->render_joins();
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

        $skip = $this->skip;
        $take = $this->take;

        if ($skip || $take) {
            $query .= ' ' . $this->render_skip_and_take($skip, $take);
        }

        return $query;
    }

    /**
     * Render the `ORDER` clause.
     *
     * @param mixed[] $order
     */
    private function render_order(array $order): string
    {
        if (count($order) == 1) {
            $raw = $order[0];
            assert(is_string($raw));
            $rendered = preg_replace(
                '/-([a-zA-Z0-9_]+)/',
                '$1 DESC',
                $raw
            );

            return 'ORDER BY ' . $rendered;
        }

        $connection = $this->model->connection;

        $field = array_shift($order);
        assert(is_string($field));
        $field_values = is_array($order[0]) ? $order[0] : $order;
        $field_values = array_map(function ($v) use ($connection) {
            return $connection->quote($v);
        }, $field_values);

        return "ORDER BY FIELD($field, " . implode(', ', $field_values) . ")";
    }

    /**
     * Render the `LIMIT` and `OFFSET` clauses.
     */
    private function render_skip_and_take(?int $skip, ?int $take): string
    {
        if ($skip && $take) {
            return "LIMIT $skip, $take";
        } elseif ($skip) {
            return "LIMIT $skip, " . self::LIMIT_MAX;
        } elseif ($take) {
            return "LIMIT $take";
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
     */
    private function resolve_statement(string $statement): string
    {
        return $this->model->resolve_statement($statement);
    }

    /**
     * Define the `SELECT` clause.
     *
     * @param non-empty-string $expression The expression of the `SELECT` clause. e.g. 'nid, title'.
     *
     * @return $this
     */
    public function select(string $expression): static
    {
        $this->select = $expression;

        return $this;
    }

    /**
     * Add a `JOIN` clause.
     *
     * @param ?non-empty-string $expression
     *     A raw `JOIN` clause.
     * @param ?Query<ActiveRecord> $query
     *     A {@link execute} instance, it is rendered as a string and used as a subquery of the `JOIN` clause.
     *     The `$options` parameter can be used to customize the output.
     * @param ?class-string<ActiveRecord> $with
     * @param non-empty-string $mode
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
     * # using an ActiveRecord class.
     *
     * $query->join(with: Comment::class);
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
    ): static {
        if ($expression) {
            $this->joins[] = $expression;

            return $this;
        }

        if ($query) {
            $this->join_with_query($query, mode: $mode, as: $as, on: $on);

            return $this;
        }

        if ($with) {
            $model = $this->model->models->model_for_record($with);

            $this->join_with_model($model, mode: $mode, as: $as, on: $on); // @phpstan-ignore-line

            return $this;
        }

        throw new LogicException("One of [ expression, query, record ] needs to be defined");
    }

    /**
     * Join a subquery to the query.
     *
     * @param Query<ActiveRecord> $query
     * @param non-empty-string $mode
     *     Join mode. Default: "INNER".
     * @param ?non-empty-string $as
     *     The alias of the subquery. Default: The query's model alias.
     * @param ?non-empty-string $on
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

        $this->joins[] = "$mode JOIN($query) `$as`$on";
        $this->joins_args = array_merge($this->joins_args, $query->args);
    }

    /**
     * Join a model to the query.
     *
     * @param Model<mixed, ActiveRecord> $model
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

        $this->joins[] = "$mode JOIN `$model->name` AS `$as` USING(`$on`)";
    }

    /**
     * Render the `on` join option.
     *
     * The method tries to determine the best solution between `ON` and `USING`.
     *
     * @param non-empty-string $column
     * @param non-empty-string $as
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

            throw new InvalidArgumentException("Unable to resolve column `$column` from model $model_class");
        }

        return "ON `$as`.`$column` = `$target->alias`.`$column`";
    }

    /**
     * Parses the conditions for the {@see where()} and {@see having()} methods.
     *
     * {@see DateTimeInterface} conditions are converted to strings.
     *
     * @param mixed ...$conditions_and_args
     *
     * @return array{ non-empty-string|null, mixed[] } An array made of the condition string and its arguments.
     */
    private function deferred_parse_conditions(mixed ...$conditions_and_args): array
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
                            $joined .= ',' . (is_numeric($value) ? $value : $this->model->connection->quote($value));
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

        $cast = $this->model->connection->driver->cast_value(...);
        $conditions_args = array_map($cast, $conditions_args);

        return [ $conditions ? '(' . $conditions . ')' : null, $conditions_args ];
    }

    /**
     * Add conditions to the SQL statement.
     *
     * Conditions can either be specified as string or array.
     *
     * 1. Pure string conditions
     *
     * If you'd like to add conditions to your statement, you could specify them in there,
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
     * This will return the orders with the `order_id` different from 123, 456 and 789.
     *
     * `$model->where([ '!order_count' => 2 ]);`
     *
     * This will return the orders with the `order_count` different from 2.
     *
     * @param mixed ...$conditions_and_args
     *
     * @return $this
     */
    public function where(...$conditions_and_args): static
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
     * @return $this
     * @see self::where()
     *
     */
    public function and(mixed ...$conditions_and_args): static
    {
        return $this->where(...$conditions_and_args);
    }

    /**
     * Defines the `ORDER` clause.
     *
     * @param string $order_or_field_name The order for the `ORDER` clause e.g.
     * 'weight, date DESC', or field to order with, in which case `$field_values` is required.
     * @param scalar[]|null $field_values Values of the field specified by `$order_or_field_name`.
     *
     * @return $this
     */
    public function order(string $order_or_field_name, mixed $field_values = null): static
    {
        $this->order = func_get_args();

        return $this;
    }

    /**
     * Defines the `GROUP BY` clause.
     *
     * @returns $this
     */
    public function group(string $group): static
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
    public function having(...$conditions_and_args): static
    {
        [ $having, $having_args ] = $this->deferred_parse_conditions(...$conditions_and_args);

        assert($having !== null);

        $this->having = $having;
        $this->having_args = $having_args;

        return $this;
    }

    /**
     * The number of records to skip before fetching.
     *
     * @return $this
     */
    public function skip(?int $skip): static
    {
        $this->skip = $skip;

        return $this;
    }

    /**
     * The number of records to take while fetching.
     *
     * @return $this
     */
    public function take(?int $take): static
    {
        $this->take = $take;

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
    public function mode(...$mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Prepare the query.
     *
     * We use the connection's prepare() method because the statement has already been resolved
     * during the __toString() method, and we don't want for the statement to be parsed twice.
     */
    private function prepare(): Statement
    {
        return $this->model->connection->prepare((string)$this);
    }

    /**
     * Return a prepared query.
     */
    protected function get_prepared(): Statement
    {
        return $this->prepare();
    }

    /**
     * Prepare and executes the query.
     */
    private function execute(): Statement
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
     * @return array{ 0: PDO::FETCH_*, 1?: mixed, 2?: mixed }
     */
    private function resolve_fetch_mode(mixed ...$mode): array
    {
        if ($mode) {
            $args = $mode;
        } elseif ($this->mode) {
            $args = $this->mode;
        } elseif ($this->select ?? null) {
            $args = [ PDO::FETCH_ASSOC ];
        } elseif ($this->model->activerecord_class) {
            $args = [ PDO::FETCH_CLASS, $this->model->activerecord_class, [ $this->model ] ];
        } else {
            $args = [ PDO::FETCH_CLASS, ActiveRecord::class, [ $this->model ] ];
        }

        // @phpstan-ignore-next-line
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
        return $this->execute()->all(...$this->resolve_fetch_mode(...$mode));
    }

    /**
     * Getter for the {@see $all} magic property.
     *
     * @return TRecord[]|mixed[]
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
    public function one(...$mode): mixed
    {
        $query = (clone $this)->take(1);

        return $query
            ->execute()
            ->mode(...$this->resolve_fetch_mode(...$mode))
            ->one;
    }

    /**
     * @see $one
     */
    protected function get_one(): mixed
    {
        return $this->one();
    }

    /**
     * Execute the query and return an array of key/value pairs, where _key_ is the value of
     * the first column and _value_ the value of the second column.
     *
     * @return array<string, string>
     *
     * @see $pairs
     */
    protected function get_pairs(): array
    {
        // @phpstan-ignore-next-line
        return $this->all(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Returns the first column of the first row.
     */
    protected function get_rc(): int|string|false|null
    {
        return (clone $this)->take(1)->execute()->rc;
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
    public function exists($key = null) // @phpstan-ignore-line
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
                ->take(1)
                ->rc;
        }

        #
        # Checking if the query matches the specified record keys.
        #

        $rc = $query
            ->select('`{primary}`')
            ->and([ '{primary}' => $key ])
            ->skip(null)
            ->take(null)
            ->all(PDO::FETCH_COLUMN);

        if ($rc && is_array($key)) {
            $exists = array_fill_keys($key, false);

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

    protected function get_exists(): bool
    {
        // @phpstan-ignore-next-line
        return $this->exists();
    }

    /**
     * Handle all the computations.
     *
     * @param non-empty-string $method
     * @param non-empty-string|null $column
     *
     * @return string|array<string, string>
     */
    private function compute(string $method, string $column = null): string|array
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

        // @phpstan-ignore-next-line
        return $statement->rc;
    }

    /**
     * Implement the 'COUNT' computation.
     *
     * @param non-empty-string|null $column The name of the column to count.
     *
     * @return int|array<non-empty-string, int>
     */
    public function count(string $column = null): int|array
    {
        // @phpstan-ignore-next-line
        return $this->compute('COUNT', $column);
    }

    /**
     * @return int|array<non-empty-string, int>
     *
     * @see $count
     */
    protected function get_count(): int|array
    {
        return $this->count();
    }

    /**
     * Implement the 'AVG' computation.
     *
     * @param non-empty-string $column
     */
    public function average(string $column): int
    {
        // @phpstan-ignore-next-line
        return $this->compute('AVG', $column);
    }

    /**
     * Implement the 'MIN' computation.
     *
     * @param non-empty-string $column
     */
    public function minimum(string $column): int|string
    {
        // @phpstan-ignore-next-line
        return $this->compute('MIN', $column);
    }

    /**
     * Implement the 'MAX' computation.
     *
     * @param non-empty-string $column
     */
    public function maximum(string $column): int|string
    {
        // @phpstan-ignore-next-line
        return $this->compute('MAX', $column);
    }

    /**
     * Implement the 'SUM' computation.
     *
     * @param non-empty-string $column
     */
    public function sum(string $column): int
    {
        // @phpstan-ignore-next-line
        return $this->compute('SUM', $column);
    }

    /**
     * Delete the records matching the conditions and range of the query.
     *
     * @param ?string $tables When using a JOIN, `$tables` is used to specify the tables in which
     * records should be deleted. Default: The alias of queried model, only if at least one join
     * clause has been defined using the {@link join()} method.
     *
     * @todo-20140901: reflect on join to add the required tables by default, discarding tables
     * joined with the LEFT mode.
     */
    public function delete(string $tables = null): Statement
    {
        if (!$tables && $this->joins) {
            $tables = "`{alias}`";
        }

        if ($tables) {
            $query = "DELETE $tables FROM {self} AS `{alias}`";
        } else {
            $query = "DELETE FROM {self}";
        }

        $query .= $this->render_main();

        return $this->model->execute($query, $this->args);
    }

    #
    # Batches
    #

    public const DEFAULT_BATCH_SIZE = 1000;

    private int $batch_size = self::DEFAULT_BATCH_SIZE;

    public function batch_size(int $batch_size): static
    {
        $this->batch_size = $batch_size;

        return $this;
    }

    /**
     * Return an iterator for the query.
     */
    public function getIterator(): Traversable
    {
        $skip = $this->skip;
        $take = $this->batch_size;
        $query = (clone $this)->take($take);

        do {
            $all = $query->all();

            foreach ($all as $one) {
                // @phpstan-ignore-next-line
                yield $one;
            }

            if (count($all) < $take) {
                return;
            }

            $skip += $take;
            $query->skip($skip);
        } while (true);
    }
}
