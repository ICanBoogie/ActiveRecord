<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;

use function array_fill_keys;
use function array_keys;
use function array_shift;
use function count;
use function get_parent_class;
use function implode;

/**
 * Base class for activerecord models.
 *
 * @template TKey of scalar|scalar[]
 * @template TValue of ActiveRecord
 *
 * @property-read Model|null $parent Parent model.
 */
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

    public function __construct(
        Connection $connection,
        public readonly ModelProvider $models,
        ModelDefinition $definition
    ) {
        $this->activerecord_class = $definition->activerecord_class; // @phpstan-ignore-line
        $this->query_class = $definition->query_class;

        $parent = $this->resolve_parent($models);

        parent::__construct($connection, $definition->table, $parent);

        $this->relations = new RelationCollection($this, $definition->association);
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
     * Finds a record or a collection of records.
     *
     * @param int|non-empty-string ...$keys
     *
     * @return TValue|TValue[] A record or a set of records.
     * @throws RecordNotFound when the record, or one or more records of the records
     * set couldn't be found.
     */
    public function find(int|string ...$keys)
    {
        if (count($keys) == 1) {
            $key = current($keys);

            return $this->find_one($key);
        }

        return $this->find_many($keys);
    }

    /**
     * Finds one records.
     *
     * @param int|non-empty-string $key
     *
     * @return ActiveRecord&TValue
     */
    private function find_one(int|string $key): ActiveRecord
    {
        assert(is_string($this->primary));

        /** @var TValue|false $record */
        $record = $this->where([ $this->primary => $key ])->one;

        if (!$record) {
            throw new RecordNotFound(
                "No $this->activerecord_class for key $key",
                [ $key => null ]
            );
        }

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
        $records = $missing = array_fill_keys($keys, value: null);

        if ($missing) {
            $primary = $this->primary;

            assert(is_string($primary));

            $query_records = $this->where([ $primary => array_keys($missing) ])->all;

            foreach ($query_records as $record) {
                $key = $record->$primary;
                $records[$key] = $record;
                unset($missing[$key]);
            }
        }

        /** @var array<int|non-empty-string, TValue> $records */

        if ($missing) {
            if (count($missing) > 1) {
                throw new RecordNotFound(
                    "No $this->activerecord_class for keys " . implode(', ', array_keys($missing)),
                    $records
                );
            }

            $key = array_keys($missing);
            $key = array_shift($key);

            throw new RecordNotFound(
                "No $this->activerecord_class for key $key",
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
     * @return Query<TValue>
     *
     * @see Query::where()
     */
    public function where(mixed ...$conditions_and_args): Query
    {
        return $this->query()->where(...$conditions_and_args);
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
