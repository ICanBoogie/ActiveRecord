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
use ArrayAccess;
use ICanBoogie\ActiveRecord;
use ICanBoogie\OffsetNotWritable;
use ICanBoogie\Prototype\MethodNotDefined;
use LogicException;

use function array_combine;
use function array_fill;
use function array_keys;
use function count;
use function func_get_args;
use function implode;
use function is_array;
use function is_callable;
use function method_exists;

/**
 * Base class for activerecord models.
 *
 * @template TKey of int|string|string[]
 * @template TValue of ActiveRecord
 *
 * @implements ArrayAccess<TKey, TValue>
 *
 * @method Query select($expression) The method is forwarded to Query::select().
 * @method Query join($expression) The method is forwarded to Query::join().
 * @method Query where($conditions, $conditions_args = null, $_ = null)
 *     The method is forwarded to {@link Query::where}.
 * @method Query group($group) The method is forwarded to Query::group().
 * @method Query order(...$order) The method is forwarded to @link Query::order().
 * @method Query limit($limit, $offset = null) The method is forwarded to Query::limit().
 * @method Query offset($offset) The method is forwarded to Query::offset().
 * @method bool exists($key = null) The method is forwarded to Query::exists().
 * @method mixed count($column = null) The method is forwarded to Query::count().
 * @method string average($column) The method is forwarded to Query::average().
 * @method string maximum($column) The method is forwarded to Query::maximum().
 * @method string minimum($column) The method is forwarded to Query::minimum().
 * @method int sum($column) The method is forwarded to Query::sum().
 * @method array all() The method is forwarded to Query::all().
 * @method ActiveRecord one() The method is forwarded to Query::one().
 * @method ActiveRecord new(array $properties = []) Instantiate a new record.
 *
 * @property-read Model|null $parent Parent model.
 * @property-read array $all Retrieve all the records from the model.
 * @property-read int $count The number of records of the model.
 * @property-read bool $exists Whether the SQL table associated with the model exists.
 * @property-read ActiveRecord $one Retrieve the first record from the mode.
 * @property ActiveRecordCache $activerecord_cache The cache use to store activerecords.
 *
 */
#[AllowDynamicProperties]
class Model extends Table implements ArrayAccess
{
    /**
     * Active record instances class.
     *
     * @var class-string<TValue>
     */
    public readonly string $activerecord_class;

    /**
     * @var class-string<Query<TValue>>
     */
    private readonly string $query_class;

    private readonly ModelAttributes $attributes;

    /**
     * The identifier of the model.
     */
    public readonly string $id;

    /**
     * The relations of this model to other models.
     */
    public readonly RelationCollection $relations;

    /**
     * Returns the records cache.
     *
     * **Note:** The method needs to be implemented through prototype bindings.
     */
    protected function lazy_get_activerecord_cache(): ActiveRecordCache
    {
        /** @phpstan-ignore-next-line */
        return parent::lazy_get_activerecord_cache();
    }

    public function __construct(
        Connection $connection,
        public readonly ModelProvider $models,
        ModelAttributes $attributes
    ) {
        $this->attributes = $attributes;
        $this->id = $attributes->id;
        $this->relations = new RelationCollection($this);

        $parent = null;

        if ($attributes->extends) {
            $parent = $models->model_for_id($attributes->extends);
        }

        parent::__construct($connection, $attributes, $parent);

        /** @phpstan-ignore-next-line */
        $this->activerecord_class = $attributes->activerecord_class
            ?? throw new LogicException("Missing ActiveRecord class for model '$this->id'");
        /** @phpstan-ignore-next-line */
        $this->query_class = $attributes->query_class
            ?? Query::class;

        $this->resolve_relations();
    }

//    // @codeCoverageIgnoreStart
//    /**
//     * @return array<string, mixed>
//     */
//    public function __debugInfo(): array
//    {
//        return [
//
//            'id' => $this->id,
//            'name' => "$this->name ($this->unprefixed_name)",
//            'parent' => $this->parent ? $this->parent->id . " of " . get_class($this->parent) : null,
//            'parent_model' => $this->parent_model
//                ? $this->parent_model->id . " of " . get_class($this->parent_model)
//                : null,
//            'relations' => $this->relations
//
//        ];
//    }
//    // @codeCoverageIgnoreEnd

    /**
     * Resolves relations with other models.
     */
    private function resolve_relations(): void
    {
        $association = $this->attributes->association;

        if (!$association) {
            return;
        }

        # belongs_to

        $belongs_to = $association->belongs_to;

        if ($belongs_to) {
            foreach ($belongs_to as $r) {
                $this->belongs_to(
                    related: $r->model_id,
                    local_key: $r->local_key,
                    foreign_key: $r->foreign_key,
                    as: $r->as,
                );
            }
        }

        # has_many

        $has_many = $association->has_many;

        if ($has_many) {
            foreach ($has_many as $r) {
                $this->has_many(
                    related: $r->model_id,
                    foreign_key: $r->foreign_key,
                    as: $r->as,
                    through: $r->through,
                );
            }
        }
    }

    /**
     * @param string $related A module identifier.
     */
    public function belongs_to(
        string $related,
        string $local_key,
        string $foreign_key,
        string $as,
    ): self {
        $this->relations->belongs_to(
            related: $related,
            local_key: $local_key,
            foreign_key: $foreign_key,
            as: $as,
        );

        return $this;
    }

    /**
     * @param string $related A module identifier.
     */
    public function has_many(
        string $related,
        string $foreign_key,
        string $as,
        ?string $through = null,
    ): self {
        $this->relations->has_many(
            related: $related,
            local_key: $this->primary,
            foreign_key: $foreign_key,
            as: $as,
            through: $through,
        );

        return $this;
    }

    /**
     * Handles query methods, dynamic filters, scopes, and relations.
     *
     * @inheritdoc
     */
    public function __call($method, $arguments)
    {
        if ($method == 'new') {
            return $this->new_record(...$arguments);
        }

        if (
            method_exists($this->query_class, $method)
            || str_starts_with($method, 'filter_by_')
            || method_exists($this, 'scope_' . $method)
        ) {
            return $this->query()->$method(...$arguments);
        }

        if (is_callable([ $this->relations, $method ])) {
            return $this->relations->$method(...$arguments);
        }

        return parent::__call($method, $arguments);
    }

    /**
     * Overrides the method to handle scopes.
     */
    public function __get($property)
    {
        $method = 'scope_' . $property;

        if (method_exists($this, $method)) {
            return $this->$method($this->query());
        }

        return parent::__get($property);
    }

    /**
     * Finds a record or a collection of records.
     *
     * @param mixed $key A key, multiple keys, or an array of keys.
     *
     * @return TValue|TValue[] A record or a set of records.
     * @throws RecordNotFound when the record, or one or more records of the records
     * set, could not be found.
     *
     */
    public function find(mixed $key)
    {
        $args = func_get_args();
        $n = count($args);

        if (!$n) {
            throw new \BadMethodCallException("Expected at least one argument.");
        }

        if (count($args) == 1) {
            $key = $args[0];

            if (!is_array($key)) {
                return $this->find_one($key);
            }

            $args = $key;
        }

        return $this->find_many($args);
    }

    /**
     * Finds one records.
     *
     * @param TKey $key
     *
     * @return TValue
     */
    private function find_one($key): ActiveRecord
    {
        $record = $this->activerecord_cache->retrieve($key);

        if ($record) {
            return $record;
        }

        $record = $this->where([ $this->primary => $key ])->one;

        if (!$record) {
            throw new RecordNotFound(
                "Record <q>{$key}</q> does not exists in model <q>{$this->id}</q>.",
                [ $key => null ]
            );
        }

        $this->activerecord_cache->store($record);

        return $record;
    }

    /**
     * Finds many records.
     *
     * @param array<TKey> $keys
     *
     * @return TValue[]
     */
    private function find_many(array $keys): array
    {
        $records = array_combine($keys, array_fill(0, count($keys), null));
        $missing = $records;

        foreach ($records as $key => $dummy) {
            $record = $this->activerecord_cache->retrieve($key);

            if (!$record) {
                continue;
            }

            $records[$key] = $record;
            unset($missing[$key]);
        }

        if ($missing) {
            $primary = $this->primary;
            $query_records = $this->where([ $primary => array_keys($missing) ])->all;

            foreach ($query_records as $record) {
                $key = $record->$primary;
                $records[$key] = $record;
                unset($missing[$key]);

                $this->activerecord_cache->store($record);
            }
        }

        if ($missing) {
            if (count($missing) > 1) {
                throw new RecordNotFound(
                    "Records " . implode(', ', array_keys($missing)) . " do not exists in model <q>{$this->id}</q>.",
                    $records
                );
            }

            $key = array_keys($missing);
            $key = \array_shift($key);

            throw new RecordNotFound(
                "Record <q>{$key}</q> does not exists in model <q>{$this->id}</q>.",
                $records
            );
        }

        return $records;
    }

    /**
     * @param mixed ...$conditions_and_args
     *
     * @return Query<TValue>
     */
    public function query(...$conditions_and_args): Query
    {
        $class = $this->query_class;
        $query = new $class($this);

        if ($conditions_and_args) {
            $query->where(...$conditions_and_args);
        }

        return $query;
    }

    /**
     * Because records are cached, we need to remove the record from the cache when it is saved,
     * so that loading the record again returns the updated record, not the one in the cache.
     *
     * @inheritdoc
     */
    public function save(array $properties, $key = null, array $options = [])
    {
        if ($key) {
            $this->activerecord_cache->eliminate($key);
        }

        return parent::save($properties, $key, $options);
    }

    /**
     * Eliminates the record from the cache.
     *
     * @inheritdoc
     */
    public function delete($key)
    {
        $this->activerecord_cache->eliminate($key);

        return parent::delete($key);
    }

    /**
     * Checks that the SQL table associated with the model exists.
     */
    protected function get_exists(): bool
    {
        return $this->exists();
    }

    /**
     * Returns the number of records of the model.
     */
    protected function get_count(): int
    {
        return $this->count();
    }

    /**
     * Returns all the records of the model.
     *
     * @return TValue[]
     */
    protected function get_all(): array
    {
        return $this->all();
    }

    /**
     * Returns the first record of the model.
     *
     * @return TValue
     */
    protected function get_one(): ActiveRecord
    {
        return $this->one();
    }

    /**
     * Checks if the model has a given scope.
     *
     * Scopes are defined using method with the "scope_" prefix. As an example, the `visible`
     * scope can be defined by implementing the `scope_visible` method.
     *
     * @param string $name Scope name.
     *
     * @return bool
     */
    public function has_scope(string $name): bool
    {
        return method_exists($this, 'scope_' . $name);
    }

    /**
     * Invokes a given scope.
     *
     * @param string $scope_name Name of the scope to apply to the query.
     * @param array $scope_args Arguments to forward to the scope method. The first argument must
     * be a {@link Query} instance.
     *
     * @return Query<TValue>
     * @throws ScopeNotDefined when the specified scope is not defined.
     *
     */
    public function scope(string $scope_name, array $scope_args = []): Query
    {
        try {
            return $this->{'scope_' . $scope_name}(...$scope_args);
        } catch (MethodNotDefined $e) {
            throw new ScopeNotDefined($scope_name, $this);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws OffsetNotWritable when one tries to write an offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new OffsetNotWritable([ $offset, $this ]);
    }

    /**
     * Alias to {@link exists()}.
     *
     * @param int $key ActiveRecord identifier.
     *
     * @return bool
     */
    public function offsetExists(mixed $key): bool
    {
        return $this->exists($key);
    }

    /**
     * Alias to {@link delete()}.
     *
     * @param int $key ActiveRecord identifier.
     */
    public function offsetUnset(mixed $key): void
    {
        $this->delete($key);
    }

    /**
     * Alias to {@link find()}.
     *
     * @param int $key ActiveRecord identifier.
     *
     * @return TValue
     */
    public function offsetGet(mixed $key): ActiveRecord
    {
        return $this->find($key);
    }

    /**
     * Creates a new ActiveRecord instance.
     *
     * The class of the instance is defined by the {@link $activerecord_class} property.
     *
     * @param array<string, mixed> $properties Optional properties to instantiate the record with.
     *
     * @retrun TValue
     */
    protected function new_record(array $properties = []): ActiveRecord
    {
        $class = $this->activerecord_class;

        return $properties ? $class::from($properties, [ $this ]) : new $class($this);
    }
}
