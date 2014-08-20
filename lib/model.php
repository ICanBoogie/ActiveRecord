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
use ICanBoogie\Prototype;

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
 * @property-read array $all Retrieve all the records from the model.
 * @property-read string $activerecord_class Class of the active records of the model.
 * @property-read int $count The number of records of the model.
 * @property-read bool $exists Whether the SQL table associated with the model exists.
 * @property-read string $id The identifier of the model.
 * @property-read ActiveRecord Retrieve the first record from the mode.
 * @property ActiveRecordCacheInterface $activerecord_cache The cache use to store activerecords.
 * @property-read Model $parent_model The parent model.
 * @property-read array $relations The relations of this model to other models.
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
	 * @var array
	 */
	protected $relations = [];

	/**
	 * Return the relations of this model to other models.
	 *
	 * @return array
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
	 * Handles the _belongs to_ relationship of the model.
	 *
	 * <pre>
	 * $cars->belongs_to([ $drivers, $brands ]);
	 * # or
	 * $cars->belongs_to([ 'drivers', 'brands' ]);
	 * # or
	 * $cars->belongs_to($drivers, $brands);
	 * # or
	 * $cars->belongs_to($drivers);
	 * $cars->belongs_to($brands);
	 * </pre>
	 *
	 * @param string|array $belongs_to
	 *
	 * @return Model
	 *
	 * @throws RelationError if the class of the active record is `ICanBoogie\ActiveRecord`.
	 */
	public function belongs_to($belongs_to)
	{
		if (func_num_args() > 1)
		{
			$belongs_to = func_get_args();
		}

		if (is_array($belongs_to))
		{
			foreach ($belongs_to as $b)
			{
				$this->belongs_to($b);
			}

			return $this;
		}

		if ($belongs_to instanceof self)
		{
			$belongs_to_model = $belongs_to;
			$belongs_to_id = $belongs_to->id;
		}
		else
		{
			$belongs_to_model = null;
			$belongs_to_id = $belongs_to;
		}

		$activerecord_class = $this->activerecord_class;

		if (!$activerecord_class || $activerecord_class == 'ICanBoogie\ActiveRecord')
		{
			throw new RelationError('The Active Record class cannot be <code>ICanBoogie\ActiveRecord</code> for a <em>belongs to</em> relationship.');
		}

		$prototype = Prototype::from($activerecord_class);
		$getter_name = 'lazy_get_' . \ICanBoogie\singularize($belongs_to_id);

		$prototype[$getter_name] = function(ActiveRecord $ar) use($belongs_to_model, $belongs_to_id)
		{
			$model = $belongs_to_model ? $belongs_to_model : get_model($belongs_to_id);
			$primary = $model->primary;
			$key = $ar->$primary;

			return $key ? $model[$key] : null;
		};

		return $this;
	}

	/**
	 * Define a one-to-many relation.
	 *
	 * <pre>
	 * $this->has_many('comments');
	 * $this->has_many([ 'comments', 'attachments' ]);
	 * $this->has_many([ [ 'comments', [ 'as' => 'comments' ] ], 'attachments' ]);
	 * </pre>
	 *
	 * @param Model|string $related The related model can be specified using its instance or its
	 * identifier.
	 * @param array $options the following options are available:
	 *
	 * - `local_key`: The name of the local key. Default: The parent model's primary key.
	 * - `foreign_key`: The name of the foreign key. Default: The parent model's primary key.
	 * - `as`: The name of the magic property to add to the prototype. Default: a plural name
	 * resolved from the foreign model's id.
	 *
	 * @see HasManyRelation
	 */
	public function has_many($related, array $options=[])
	{
		if (is_array($related))
		{
			$relation_list = $related;

			foreach ($relation_list as $relation)
			{
				list($related, $options) = ((array) $relation) + [ 1 => [] ];

				$this->relations[] = new HasManyRelation($this, $related, $options);
			}

			return;
		}

		$this->relations[] = new HasManyRelation($this, $related, $options);
	}

	/**
	 * Handles query methods, dynamic filters and scopes.
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

		return parent::__call($method, $arguments);
	}

	/**
	 * Overrides the method to handle scopes.
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
	 * @TODO-20140414: Remove the method and use {@link $activerecord_cache}
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
	 * @TODO-20140414: Remove the method and use {@link $activerecord_cache}
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
	 * @TODO-20140414: Remove the method and use {@link $activerecord_cache}
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
	 * Offsets are not settable.
	 *
	 * @throws OffsetNotWritable when one tries to write an offset.
	 */
	public function offsetSet($offset, $value)
	{
		throw new OffsetNotWritable([ $offset, $this ]);
	}

	/**
	 * Checks if the record identified by the given key exists.
	 *
	 * The call is forwarded to {@link exists()}.
	 */
	public function offsetExists($key)
	{
		return $this->exists($key);
	}

	/**
	 * Deletes the record specified by the given key.
	 *
	 * @see Model::delete();
	 */
	public function offsetUnset($key)
	{
		$this->delete($key);
	}

	/**
	 * Alias for the {@link find()} method.
	 *
	 * @see Model::find()
	 */
	public function offsetGet($key)
	{
		return $this->find($key);
	}

	/**
	 * Creates a new active record instance.
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

/**
 * Exception thrown when an active record cannot be found.
 *
 * @property-read array[int]ActiveRecord|null $records
 */
class RecordNotFound extends \LogicException implements Exception
{
	use \ICanBoogie\GetterTrait;

	/**
	 * A key/value array where keys are the identifier of the record, and the value is the result
	 * of finding the record. If the record was found the value is a {@link ActiveRecord}
	 * object, otherwise the `null` value.
	 *
	 * @var array[int]ActiveRecord|null
	 */
	private $records;

	protected function get_records()
	{
		return $this->records;
	}

	/**
	 * Initializes the {@link $records} property.
	 *
	 * @param string $message
	 * @param array $records
	 * @param int $code Defaults to 404.
	 * @param \Exception $previous Previous exception.
	 */
	public function __construct($message, array $records, $code=404, \Exception $previous=null)
	{
		$this->records = $records;

		parent::__construct($message, $code, $previous);
	}
}

/**
 * Exception thrown when a scope is not defined.
 *
 * @property-read string $scope_name
 * @property-read Model $model
 */
class ScopeNotDefined extends \LogicException implements Exception
{
	use \ICanBoogie\GetterTrait;

	/**
	 * Name of the scope.
	 *
	 * @var string
	 */
	private $scope_name;

	protected function get_scope_name()
	{
		return $this->scope_name;
	}

	/**
	 * Model on which the scope was invoked.
	 *
	 * @var Model
	 */
	private $model;

	protected function get_model()
	{
		return $this->model;
	}

	/**
	 * Initializes the {@link $scope_name} and {@link $model} properties.
	 *
	 * @param string $scope_name Name of the scope.
	 * @param Model $model Model on which the scope was invoked.
	 * @param int $code Default to 404.
	 * @param \Exception $previous Previous exception.
	 */
	public function __construct($scope_name, Model $model, $code=500, \Exception $previous=null)
	{
		$this->scope_name = $scope_name;
		$this->model = $model;

		parent::__construct("Unknown scope `{$scope_name}` for model `{$model->name_unprefixed}`.", $code, $previous);
	}
}