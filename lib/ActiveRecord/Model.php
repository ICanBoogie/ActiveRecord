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
 * @property-read Model|null $parent Parent model.
 * @property-read array $all Retrieve all the records from the model.
 * @property-read int $count The number of records of the model.
 * @property-read bool $exists Whether the SQL table associated with the model exists.
 * @property-read ActiveRecord $one Retrieve the first record from the mode.
 * @property ActiveRecordCache $activerecord_cache The cache use to store activerecords.
 */
#[AllowDynamicProperties]
class Model extends Table
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
     * @return ActiveRecord<TValue>
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
     * Returns a new query with the WHERE clause initialized with the provided conditions and arguments.
     *
     * @param ...$conditions_and_args
     *
     * @return Query<TValue>
     */
    public function where(...$conditions_and_args): Query
    {
        return $this->query()->where(...$conditions_and_args);
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
