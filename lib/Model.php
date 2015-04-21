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

use ICanBoogie\ActiveRecord;
use ICanBoogie\OffsetNotWritable;
use ICanBoogie\Prototype\MethodNotDefined;

/**
 * Base class for activerecord models.
 *
 * @method Query select() select($expression) The method is forwarded to {@link Query::select}.
 * @method Query join() join($expression) The method is forwarded to {@link Query::join}.
 * @method Query where() where($conditions, $conditions_args=null) The method is forwarded to {@link Query::where}.
 * @method Query group() group($group) The method is forwarded to {@link Query::group}.
 * @method Query order() order($order) The method is forwarded to {@link Query::order}.
 * @method Query limit() limit($limit, $offset=null) The method is forwarded to {@link Query::limit}.
 * @method Query offset() offset($offset) The method is forwarded to {@link Query::offset}.
 * @method bool exists() exists($key=null) The method is forwarded to {@link Query::exists}.
 * @method mixed count() count($column=null) The method is forwarded to {@link Query::count}.
 * @method string average() average($column) The method is forwarded to {@link Query::average}.
 * @method string maximum() maximum($column) The method is forwarded to {@link Query::maximum}.
 * @method string minimum() minimum($column) The method is forwarded to {@link Query::minimum}.
 * @method int sum() sum($column) The method is forwarded to {@link Query::sum}.
 * @method array all() all() The method is forwarded to {@link Query::all}.
 * @method ActiveRecord one() one() The method is forwarded to {@link Query::one}.
 * @method ActiveRecord new() new(array $properties = []) Instantiate a new record.
 *
 * @method Model belongs_to() belongs_to($definition) Add a _belongs_to_ relation.
 * @method Model has_many() has_many($related, $options=[]) Adds a _has_many_ relation.
 *
 * @property-read Model|null $parent Parent model.
 * @property-read ModelCollection $models
 * @property-read array $all Retrieve all the records from the model.
 * @property-read string $activerecord_class Class of the active records of the model.
 * @property-read int $count The number of records of the model.
 * @property-read bool $exists Whether the SQL table associated with the model exists.
 * @property-read string $id The identifier of the model.
 * @property-read ActiveRecord $one Retrieve the first record from the mode.
 * @property ActiveRecordCache $activerecord_cache The cache use to store activerecords.
 * @property-read Model $parent_model The parent model.
 * @property-read Relation[] $relations The relations of this model to other models.
 */
class Model extends Table implements \ArrayAccess
{
	const ACTIVERECORD_CLASS = 'activerecord_class';
	const BELONGS_TO = 'belongs_to';
	const CLASSNAME = 'class';
	const HAS_MANY = 'has_many';
	const ID = 'id';

	/**
	 * @var ModelCollection
	 */
	private $models;

	protected function get_models()
	{
		return $this->models;
	}

	/**
	 * Active record instances class.
	 *
	 * @var string
	 */
	protected $activerecord_class;

	/**
	 * Attributes of the model.
	 *
	 * @var array[string]mixed
	 */
	protected $attributes;

	/**
	 * The parent model of the model.
	 *
	 * The parent model and the {@link parent} may be different if the model does not have a
	 * schema but inherits it from its parent.
	 *
	 * @var Model
	 */
	protected $parent_model;

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
	protected $relations;

	/**
	 * Return the relations of this model to other models.
	 *
	 * @return RelationCollection
	 */
	protected function get_relations()
	{
		return $this->relations;
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

		#
		# Resolve the active record class.
		#

		$this->activerecord_class = $this->resolve_activerecord_class();
		$this->resolve_relations();
	}

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

		if (!$attributes[self::ID])
		{
			$attributes[self::ID] = $attributes[self::NAME];
		}

		$this->parent_model = $extends = $attributes[self::EXTENDING];

		if ($extends && !$attributes[self::SCHEMA])
		{
			$attributes[self::NAME] = $extends->unprefixed_name;
			$attributes[self::SCHEMA] = $extends->schema_options;
			$attributes[self::EXTENDING] = $extends->parent;

			if (!$attributes[self::ACTIVERECORD_CLASS])
			{
				$attributes[self::ACTIVERECORD_CLASS] = $extends->activerecord_class;
			}
		}

		return $attributes;
	}

	/**
	 * Resolves ActiveRecord class.
	 *
	 * @return string
	 */
	private function resolve_activerecord_class()
	{
		$activerecord_class = $this->attributes[self::ACTIVERECORD_CLASS];

		if (!$activerecord_class && $this->parent)
		{
			$activerecord_class = $this->parent->activerecord_class;
		}

		return $activerecord_class;
	}

	/**
	 * Resolves relations with other models.
	 */
	private function resolve_relations()
	{
		$attributes = $this->attributes;

		# belongs_to

		$belongs_to = $attributes[self::BELONGS_TO];

		if ($belongs_to)
		{
			$this->belongs_to($belongs_to);
		}

		# has_many

		$has_many = $attributes[self::HAS_MANY];

		if ($has_many)
		{
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
		if ($method == 'new')
		{
			return call_user_func_array([ $this, 'new_record' ], $arguments);
		}

		if (is_callable([ Query::class, $method ])
		|| strpos($method, 'filter_by_') === 0
		|| method_exists($this, 'scope_' . $method))
		{
			$query = new Query($this);

			return call_user_func_array([ $query, $method ], $arguments);
		}

		if (is_callable([ RelationCollection::class, $method ]))
		{
			return call_user_func_array([ $this->relations, $method ], $arguments);
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

		if (method_exists($this, $method))
		{
			return $this->$method(new Query($this));
		}

		return parent::__get($property);
	}

	/**
	 * Returns the identifier of the model.
	 *
	 * @return string
	 */
	protected function get_id()
	{
		return $this->attributes[self::ID];
	}

	/**
	 * Returns the class of the active records of the model.
	 *
	 * @return string
	 */
	protected function get_activerecord_class()
	{
		return $this->activerecord_class;
	}

	/**
	 * Finds a record or a collection of records.
	 *
	 * @param mixed $key A key, multiple keys, or an array of keys.
	 *
	 * @throws RecordNotFound when the record, or one or more records of the records
	 * set, could not be found.
	 *
	 * @return ActiveRecord|ActiveRecord[] A record or a set of records.
	 */
	public function find($key)
	{
		$args = func_get_args();
		$n = count($args);

		if (!$n)
		{
			throw new \BadMethodCallException("Expected at least one argument.");
		}

		if (count($args) == 1)
		{
			$key = $args[0];

			if (!is_array($key))
			{
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
	private function find_one($key)
	{
		$record = $this->activerecord_cache->retrieve($key);

		if ($record === null)
		{
			$record = $this->where([ $this->primary => $key ])->one;

			if (!$record)
			{
				throw new RecordNotFound
				(
					"Record <q>{$key}</q> does not exists in model <q>{$this->id}</q>.", [ $key => null ]
				);
			}

			$this->activerecord_cache->store($record);
		}

		return $record;
	}

	/**
	 * Finds many records.
	 *
	 * @param array $keys
	 *
	 * @return ActiveRecord[]
	 */
	private function find_many(array $keys)
	{
		$records = array_combine($keys, array_fill(0, count($keys), null));
		$missing = $records;

		foreach ($records as $key => $dummy)
		{
			$record = $this->activerecord_cache->retrieve($key);

			if (!$record)
			{
				continue;
			}

			$records[$key] = $record;
			unset($missing[$key]);
		}

		if ($missing)
		{
			$primary = $this->primary;
			$query_records = $this->where([ $primary => array_keys($missing) ])->all;

			foreach ($query_records as $record)
			{
				$key = $record->$primary;
				$records[$key] = $record;
				unset($missing[$key]);

				$this->activerecord_cache->store($record);
			}
		}

		if ($missing)
		{
			if (count($missing) > 1)
			{
				throw new RecordNotFound
				(
					"Records " . implode(', ', array_keys($missing)) . " do not exists in model <q>{$this->id}</q>.", $records
				);
			}
			else
			{
				$key = array_keys($missing);
				$key = array_shift($key);

				throw new RecordNotFound
				(
					"Record <q>{$key}</q> does not exists in model <q>{$this->id}</q>.", $records
				);
			}
		}

		return $records;
	}

	/**
	 * Because records are cached, we need to remove the record from the cache when it is saved,
	 * so that loading the record again returns the updated record, not the one in the cache.
	 *
	 * @inheritdoc
	 */
	public function save(array $properties, $key = null, array $options = [])
	{
		if ($key)
		{
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
	protected function get_exists()
	{
		return $this->exists();
	}

	/**
	 * Returns the number of records of the model.
	 *
	 * @return int
	 */
	protected function get_count()
	{
		return $this->count();
	}

	/**
	 * Returns all the records of the model.
	 *
	 * @return ActiveRecord[]
	 */
	protected function get_all()
	{
		return $this->all();
	}

	/**
	 * Returns the first record of the model.
	 *
	 * @return ActiveRecord
	 */
	protected function get_one()
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
	 * @return boolean
	 */
	public function has_scope($name)
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
	 * @throws ScopeNotDefined when the specified scope is not defined.
	 *
	 * @return Query
	 */
	public function scope($scope_name, array $scope_args = [])
	{
		try
		{
			return call_user_func_array([ $this, 'scope_' . $scope_name ], $scope_args);
		}
		catch (MethodNotDefined $e)
		{
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
	protected function new_record(array $properties = [])
	{
		$class = $this->activerecord_class;

		return $properties ? $class::from($properties, [ $this ]) : new $class($this);
	}
}
