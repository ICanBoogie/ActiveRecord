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
use ICanBoogie\ActiveRecord\Config\ModelDefinition;
use ICanBoogie\OffsetNotWritable;

use function array_fill_keys;
use function array_keys;
use function array_shift;
use function count;
use function func_get_args;
use function get_parent_class;
use function implode;
use function is_array;
use function is_callable;
use function method_exists;
use function sprintf;

/**
 * Base class for activerecord models.
 *
 * @template TKey of int|non-empty-string|non-empty-string[]
 * @template TValue of ActiveRecord
 *
 * @implements ArrayAccess<TKey, TValue>
 *
 * @method Query select($expression) The method is forwarded to Query::select().
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
 *
 * @property-read Model|null $parent Parent model.
 * @property-read array $all Retrieve all the records from the model.
 * @property-read int $count The number of records of the model.
 * @property-read bool $exists Whether the SQL table associated with the model exists.
 * @property-read ActiveRecord $one Retrieve the first record from the mode.
 * @property ActiveRecordCache $activerecord_cache The cache use to store activerecords.
 */
#[AllowDynamicProperties]
class Model extends Table implements ArrayAccess
{
    /**
     * @var class-string<TValue>
     */
    public readonly string $activerecord_class;

    /**
     * @var class-string<Query<TValue>>
     */
    public readonly string $query_class;

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
        private readonly ModelDefinition $definition
    ) {
        $this->activerecord_class = $this->definition->activerecord_class; // @phpstan-ignore-line
        $this->query_class = $this->definition->query_class;

        $parent = $this->resolve_parent($models);

        parent::__construct($connection, $definition->table, $parent);

        $this->relations = new RelationCollection($this, $this->definition->association);
    }

    private function resolve_parent(ModelProvider $models): ?Model
    {
        $parent_class = get_parent_class($this->activerecord_class);

        if ($parent_class === ActiveRecord::class) {
            return null;
        }

        return $models->model_for_record($parent_class); // @phpstan-ignore-line
    }

    /**
     * Handles query methods, dynamic filters, and relations.
     *
     * @inheritdoc
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->query_class, $method) || str_starts_with($method, 'filter_by_')) {
            return $this->query()->$method(...$arguments);
        }

        if (is_callable([ $this->relations, $method ])) {
            return $this->relations->$method(...$arguments);
        }

        return parent::__call($method, $arguments);
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
                "Record <q>{$key}</q> does not exist in model <q>{$this->activerecord_class}</q>.",
                [ $key => null ]
            );
        }

        $this->activerecord_cache->store($record);

        return $record;
    }

    /**
     * Finds many records.
     *
     * @param array<int|non-empty-string> $keys
     *
     * @return array<int|non-empty-string, TValue>
     */
    private function find_many(array $keys): array
    {
        $records = $missing = array_fill_keys($keys, null);

        foreach ($keys as $key) {
            $record = $this->activerecord_cache->retrieve($key);

            if (!$record) {
                continue;
            }

            $records[$key] = $record;
            unset($missing[$key]);
        }

        if ($missing) {
            $primary = $this->primary;

            assert(is_string($primary));

            $query_records = $this->where([ $primary => array_keys($missing) ])->all;

            foreach ($query_records as $record) {
                $key = $record->$primary;
                $records[$key] = $record;
                unset($missing[$key]);

                $this->activerecord_cache->store($record);
            }
        }

        /** @var array<int|non-empty-string, TValue> $records */

        if ($missing) {
            if (count($missing) > 1) {
                throw new RecordNotFound(
                    sprintf(
                        "Records `%s` do not exist for `%s`",
                        implode('`, `', array_keys($missing)),
                        $this->activerecord_class
                    ),
                    $records
                );
            }

            $key = array_keys($missing);
            $key = array_shift($key);

            throw new RecordNotFound(
                "Record `$key` does not exist for `$this->activerecord_class`",
                $records
            );
        }

        return $records;
    }

    /**
     * Returns a new query.
     *
     * @return Query<TValue>
     */
    public function query(): Query
    {
        return new $this->query_class($this);
    }

    /**
     * Because records are cached, we need to remove the record from the cache when it is saved,
     * so that loading the record again returns the updated record, not the one in the cache.
     */
    public function save(array $values, mixed $id = null, array $options = []): mixed
    {
        if ($id) {
            $this->activerecord_cache->eliminate($id);
        }

        return parent::save($values, $id, $options);
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
     * @phpstan-return TValue
     */
    protected function get_one(): ActiveRecord
    {
        return $this->one();
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
    public function new(array $properties = []): ActiveRecord
    {
        $class = $this->activerecord_class;

        return $properties ? $class::from($properties, [ $this ]) : new $class($this);
    }
}
