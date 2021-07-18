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

use ArrayAccess;
use ICanBoogie\ActiveRecord;
use ICanBoogie\OffsetNotWritable;
use ICanBoogie\Prototype\MethodNotDefined;

/**
 * Base class for activerecord models.
 *
 * @method Query select($expression) The method is forwarded to Query::select().
 * @method Query join($expression) The method is forwarded to Query::join().
 * @method Query where($conditions, $conditions_args = null, $_ = null)
 *     The method is forwarded to {@link Query::where}.
 * @method Query group($group) The method is forwarded to Query::group().
 * @method Query order($order) The method is forwarded to Query::order().
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
 * @method Model belongs_to(...$args) Adds a _belongs_to_ relation.
 * @method Model has_many($related, $options = []) Adds a _has_many_ relation.
 *
 * @property-read Model|null $parent Parent model.
 * @property-read ModelCollection $models
 * @property-read array $all Retrieve all the records from the model.
 * @property-read class-string $activerecord_class Class of the active records of the model.
 * @property-read int $count The number of records of the model.
 * @property-read bool $exists Whether the SQL table associated with the model exists.
 * @property-read string $id The identifier of the model.
 * @property-read ActiveRecord $one Retrieve the first record from the mode.
 * @property ActiveRecordCache $activerecord_cache The cache use to store activerecords.
 * @property-read Model $parent_model The parent model.
 * @property-read RelationCollection $relations The relations of this model to other models.
 *
 * @implements ArrayAccess<int|string, ActiveRecord>
 */
class Model extends Table implements ArrayAccess
{
    public const ACTIVERECORD_CLASS = 'activerecord_class';
    public const BELONGS_TO = 'belongs_to';
    public const CLASSNAME = 'class';
    public const HAS_MANY = 'has_many';
    public const ID = 'id';
    public const QUERY_CLASS = 'query_class';

    private ModelCollection $models;

    protected function get_models(): ModelCollection
    {
        return $this->models;
    }

    /**
     * Active record instances class.
     *
     * @var class-string
     */
    private $activerecord_class;

    /**
     * @return class-string
     */
    protected function get_activerecord_class()
    {
        return $this->activerecord_class;
    }

    /**
     * @var string
     */
    private $query_class;

    /**
     * Attributes of the model.
     *
     * @var array[string]mixed
     */
    private $attributes;

    /**
     * Returns the identifier of the model.
     */
    protected function get_id(): string
    {
        return $this->attributes[self::ID];
    }

    /**
     * The parent model of the model.
     *
     * The parent model and the {@link parent} may be different if the model does not have a
     * schema but inherits it from its parent.
     *
     * @var Model
     */
    private $parent_model;

    /**
     * Return the parent mode.
     *
     * @return Model
     */
    protected function get_parent_model()
    {
        return $this->parent_model;
    }

    /**
     * The relations of this model to other models.
     *
     * @var RelationCollection
     */
    private $relations;

    protected function get_relations(): RelationCollection
    {
        return $this->relations;
    }

    /**
     * Returns the records cache.
     *
     * **Note:** The method needs to be implemented through prototype bindings.
     *
     * @return ActiveRecordCache
     */
    protected function lazy_get_activerecord_cache()
    {
        return parent::lazy_get_activerecord_cache();
    }

    /**
     * Override the constructor to provide support for the {@link ACTIVERECORD_CLASS} tag and
     * extended support for the {@link EXTENDING} tag.
     *
     * If {@link EXTENDING} is defined but the model has no schema ({@link SCHEMA} is empty),
     * the name of the model and the schema are inherited from the extended model and
     * {@link EXTENDING} is set to the parent model object. If {@link ACTIVERECORD_CLASS} is
     * empty, its value is set to the extended model's active record class.
     *
     * If {@link ACTIVERECORD_CLASS} is set, its value is saved in the
     * {@link $activerecord_class} property.
     *
     * @param ModelCollection $models
     * @param array $attributes Attributes used to construct the model.
     */
    public function __construct(ModelCollection $models, array $attributes)
    {
        $this->models = $models;
        $this->attributes = $attributes = $this->resolve_attributes($attributes);
        $this->parent = $attributes[self::EXTENDING];
        $this->relations = new RelationCollection($this);

        parent::__construct($attributes);

        $this->activerecord_class = $this->resolve_activerecord_class();
        $this->query_class = $this->resolve_query_class();
        $this->resolve_relations();
    }

    // @codeCoverageIgnoreStart
    public function __debugInfo()
    {
        return [

            'id' => $this->id,
            'name' => "$this->name ($this->unprefixed_name)",
            'parent' => $this->parent ? $this->parent->id . " of " . \get_class($this->parent) : null,
            'parent_model' => $this->parent_model
                ? $this->parent_model->id . " of " . \get_class($this->parent_model)
                : null,
            'relations' => $this->relations

        ];
    }
    // @codeCoverageIgnoreEnd

    /**
     * Resolves constructor attributes.
     *
     * The method may initialize the {@link $parent_model} property.
     *
     * @param array $attributes
     *
     * @return array
     */
    private function resolve_attributes(array $attributes)
    {
        $attributes += [

            self::ACTIVERECORD_CLASS => null,
            self::BELONGS_TO => null,
            self::EXTENDING => null,
            self::HAS_MANY => null,
            self::ID => null,
            self::SCHEMA => null

        ];

        if (!$attributes[self::ID]) {
            $attributes[self::ID] = $attributes[self::NAME];
        }

        $this->parent_model = $extends = $attributes[self::EXTENDING];

        if ($extends && !$attributes[self::SCHEMA]) {
            $attributes[self::NAME] = $extends->unprefixed_name;
            $attributes[self::SCHEMA] = $extends->schema;
            $attributes[self::EXTENDING] = $extends->parent;

            if (!$attributes[self::ACTIVERECORD_CLASS]) {
                $attributes[self::ACTIVERECORD_CLASS] = $extends->activerecord_class;
            }
        }

        return $attributes;
    }

    private function resolve_activerecord_class(): string
    {
        $activerecord_class = $this->attributes[self::ACTIVERECORD_CLASS];

        if (empty($activerecord_class)) {
            return $this->parent ? $this->parent->activerecord_class : ActiveRecord::class;
        }

        return $activerecord_class;
    }

    /**
     * @return string
     */
    private function resolve_query_class(): string
    {
        return $this->attributes[self::QUERY_CLASS] ?? Query::class;
    }

    /**
     * Resolves relations with other models.
     */
    private function resolve_relations(): void
    {
        $attributes = $this->attributes;

        # belongs_to

        $belongs_to = $attributes[self::BELONGS_TO];

        if ($belongs_to) {
            $this->belongs_to($belongs_to);
        }

        # has_many

        $has_many = $attributes[self::HAS_MANY];

        if ($has_many) {
            $this->has_many($has_many);
        }
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

        $query_class = $this->resolve_query_class();

        if (
            \method_exists($query_class, $method)
            || \strpos($method, 'filter_by_') === 0
            || \method_exists($this, 'scope_' . $method)
        ) {
            return $this->query()->$method(...$arguments);
        }

        if (\is_callable([ $this->relations, $method ])) {
            return $this->relations->$method(...$arguments);
        }

        return parent::__call($method, $arguments);
    }

    /**
     * Overrides the method to handle scopes.
     *
     * @inheritdoc
     */
    public function __get($property)
    {
        $method = 'scope_' . $property;

        if (\method_exists($this, $method)) {
            return $this->$method($this->query());
        }

        return parent::__get($property);
    }

    /**
     * Finds a record or a collection of records.
     *
     * @param mixed $key A key, multiple keys, or an array of keys.
     *
     * @return ActiveRecord|ActiveRecord[] A record or a set of records.
     * @throws RecordNotFound when the record, or one or more records of the records
     * set, could not be found.
     *
     */
    public function find($key)
    {
        $args = \func_get_args();
        $n = \count($args);

        if (!$n) {
            throw new \BadMethodCallException("Expected at least one argument.");
        }

        if (\count($args) == 1) {
            $key = $args[0];

            if (!\is_array($key)) {
                return $this->find_one($key);
            }

            $args = $key;
        }

        return $this->find_many($args);
    }

    /**
     * Finds one records.
     *
     * @param string|int $key
     *
     * @return ActiveRecord
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
     * @param array $keys
     *
     * @return ActiveRecord[]
     */
    private function find_many(array $keys): array
    {
        $records = \array_combine($keys, \array_fill(0, \count($keys), null));
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
            $query_records = $this->where([ $primary => \array_keys($missing) ])->all;

            foreach ($query_records as $record) {
                $key = $record->$primary;
                $records[$key] = $record;
                unset($missing[$key]);

                $this->activerecord_cache->store($record);
            }
        }

        if ($missing) {
            if (\count($missing) > 1) {
                throw new RecordNotFound(
                    "Records " . \implode(', ', \array_keys($missing)) . " do not exists in model <q>{$this->id}</q>.",
                    $records
                );
            }

            $key = \array_keys($missing);
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
     * @return Query
     */
    public function query(...$conditions_and_args): Query
    {
        /* @var Query $query */
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
     *
     * @return bool
     */
    protected function get_exists(): bool
    {
        return $this->exists();
    }

    /**
     * Returns the number of records of the model.
     *
     * @return int
     */
    protected function get_count(): int
    {
        return $this->count();
    }

    /**
     * Returns all the records of the model.
     *
     * @return ActiveRecord[]
     */
    protected function get_all(): array
    {
        return $this->all();
    }

    /**
     * Returns the first record of the model.
     *
     * @return ActiveRecord
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
        return \method_exists($this, 'scope_' . $name);
    }

    /**
     * Invokes a given scope.
     *
     * @param string $scope_name Name of the scope to apply to the query.
     * @param array $scope_args Arguments to forward to the scope method. The first argument must
     * be a {@link Query} instance.
     *
     * @return Query
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
    public function offsetSet($offset, $value)
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
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * Alias to {@link delete()}.
     *
     * @param int $key ActiveRecord identifier.
     */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    /**
     * Alias to {@link find()}.
     *
     * @param int $key ActiveRecord identifier.
     *
     * @return ActiveRecord
     */
    public function offsetGet($key)
    {
        return $this->find($key);
    }

    /**
     * Creates a new ActiveRecord instance.
     *
     * The class of the instance is defined by the {@link $activerecord_class} property.
     *
     * @param array $properties Optional properties to instantiate the record with.
     *
     * @return ActiveRecord
     */
    protected function new_record(array $properties = []): ActiveRecord
    {
        $class = $this->activerecord_class;

        return $properties ? $class::from($properties, [ $this ]) : new $class($this);
    }
}
