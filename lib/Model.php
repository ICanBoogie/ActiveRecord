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
 *
 * @method Model belongs_to() belongs_to($definition) Add a _belongs_to_ relation.
 * @method Model has_many() has_many($related, $options=[]) Add a _has_many_ relation.
 *
 * @property-read array $all Retrieve all the records from the model.
 * @property-read string $activerecord_class Class of the active records of the model.
 * @property-read int $count The number of records of the model.
 * @property-read bool $exists Whether the SQL table associated with the model exists.
 * @property-read string $id The identifier of the model.
 * @property-read ActiveRecord Retrieve the first record from the mode.
 * @property ActiveRecordCacheInterface $activerecord_cache The cache use to store activerecords.
 * @property-read Model $parent_model The parent model.
 * @property-read Relation[] $relations The relations of this model to other models.
 */
class Model extends Table implements \ArrayAccess
{
	// TODO-20130216: deprecate all T_*

	const T_ACTIVERECORD_CLASS = 'activerecord_class';
	const T_CLASS = 'class';
	const T_ID = 'id';

	const ACTIVERECORD_CLASS = 'activerecord_class';
	const BELONGS_TO = 'belongs_to';
	const CLASSNAME = 'class';
	const HAS_MANY = 'has_many';
	const ID = 'id';

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
	 * The parent model and the {@link parent} may be different if the model doesn't have a
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
	 * @param array $tags Tags used to construct the model.
	 */
	public function __construct(array $tags)
	{
		$this->relations = new RelationCollection($this);

		$tags += [

			self::BELONGS_TO => null,
			self::EXTENDING => null,
			self::ID => null,
			self::SCHEMA => null,
			self::ACTIVERECORD_CLASS => null,
			self::HAS_MANY => null

		];

		$this->parent_model = $extends = $tags[self::EXTENDING];

		if ($extends && !$tags[self::SCHEMA])
		{
			$tags[self::NAME] = $extends->name_unprefixed;
			$tags[self::SCHEMA] = $extends->schema;
			$tags[self::EXTENDING] = $extends->parent;

			if (!$tags[self::ACTIVERECORD_CLASS])
			{
				$tags[self::ACTIVERECORD_CLASS] = $extends->activerecord_class;
			}
		}

		if (empty($tags[self::ID]))
		{
			$tags[self::ID] = $tags[self::NAME];
		}

		$this->attributes = $tags;

		parent::__construct($tags);

		#
		# Resolve the active record class.
		#

		$activerecord_class = $tags[self::ACTIVERECORD_CLASS];

		if (!$activerecord_class && $this->parent)
		{
			$activerecord_class = $this->parent->activerecord_class;
		}

		$this->activerecord_class = $activerecord_class;

		# belongs_to

		$belongs_to = $tags[self::BELONGS_TO];

		if ($belongs_to)
		{
			$this->belongs_to($belongs_to);
		}

		# has_many

		$has_many = $tags[self::HAS_MANY];

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
		if (is_callable([ 'ICanBoogie\ActiveRecord\Query', $method ])
		|| strpos($method, 'filter_by_') === 0
		|| method_exists($this, 'scope_' . $method))
		{
			$query = new Query($this);

			return call_user_func_array([ $query, $method ], $arguments);
		}

		if (is_callable([ 'ICanBoogie\ActiveRecord\RelationCollection', $method ]))
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
	 * @param mixed $key A key or an array of keys.
	 *
	 * @throws RecordNotFound when the record, or one or more records of the records
	 * set, could not be found.
	 *
	 * @return ActiveRecord|array A record or a set of records.
	 */
	public function find($key)
	{
		if (func_num_args() > 1)
		{
			$key = func_get_args();
		}

		if (is_array($key))
		{
			$records = array_combine($key, array_fill(0, count($key), null));
			$missing = $records;

			foreach ($records as $key => $dummy)
			{
				$record = $this->retrieve($key);

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

					$this->store($record);
				}
			}

			if ($missing)
			{
				if (count($missing) > 1)
				{
					throw new RecordNotFound
					(
						"Records " . implode(', ', array_keys($missing)) . " do not exists in model <q>{$this->name_unprefixed}</q>.", $records
					);
				}
				else
				{
					$key = array_keys($missing);
					$key = array_shift($key);

					throw new RecordNotFound
					(
						"Record <q>{$key}</q> does not exists in model <q>{$this->name_unprefixed}</q>.", $records
					);
				}
			}

			return $records;
		}

		$record = $this->retrieve($key);

		if ($record === null)
		{
			$record = $this->where([ $this->primary => $key ])->one;

			if (!$record)
			{
				throw new RecordNotFound
				(
					"Record <q>{$key}</q> does not exists in model <q>{$this->name_unprefixed}</q>.", [ $key => null ]
				);
			}

			$this->store($record);
		}

		return $record;
	}

	/**
	 * Because records are cached, we need to remove the record from the cache when it is saved,
	 * so that loading the record again returns the updated record, not the one in the cache.
	 *
	 * @inheritdoc
	 */
	public function save(array $properties, $key=null, array $options=[])
	{
		if ($key)
		{
			$this->eliminate($key);
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
	 * Stores a record in the records cache.
	 *
	 * @param ActiveRecord $record The record to store.
	 *
	 * @deprecated Use {@link $activerecord_cache}
	 */
	protected function store(ActiveRecord $record)
	{
		$this->activerecord_cache->store($record);
	}

	/**
	 * Retrieves a record from the records cache.
	 *
	 * @param int $key
	 *
	 * @return ActiveRecord|null Returns the active record found in the cache or null if it wasn't
	 * there.
	 *
	 * @deprecated Use {@link $activerecord_cache}
	 */
	protected function retrieve($key)
	{
		return $this->activerecord_cache->retrieve($key);
	}

	/**
	 * Eliminates an object from the cache.
	 *
	 * @param int $key
	 *
	 * @deprecated Use {@link $activerecord_cache}
	 */
	protected function eliminate($key)
	{
		$this->activerecord_cache->eliminate($key);
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
	 * @return array[]ActiveRecord
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
	 * Calls a given scope on the active record query specified in the scope_args.
	 *
	 * @param string $scope_name Name of the scope to apply to the query.
	 * @param array $scope_args Arguments to forward to the scope method.
	 *
	 * @throws ScopeNotDefined when the specified scope is not defined.
	 *
	 * @return Query
	 */
	public function scope($scope_name, $scope_args=null)
	{
		$callback = 'scope_' . $scope_name;

		if (!method_exists($this, $callback))
		{
			throw new ScopeNotDefined($scope_name, $this);
		}

		return call_user_func_array([ $this, $callback ], $scope_args);
	}

	/*
	 * ArrayAccess implementation
	 */

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
	 * @return ActiveRecord
	 */
	public function new_record()
	{
		$class = $this->activerecord_class;

		return new $class($this);
	}
}
