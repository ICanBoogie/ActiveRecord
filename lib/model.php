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
use ICanBoogie\PropertyNotWritable;

/**
 * Base class for activerecord models.
 *
 * @method Query select() select($expression) The method is forwarded to {@link Query::select}.
 * @method Query joins() joins($expression) The method is forwarded to {@link Query::joins}.
 * @method Query where() where($conditions, $conditions_args=null) The method is forwarded to {@link Query::where}.
 * @method Query group() group($group) The method is forwarded to {@link Query::group}.
 * @method Query order() order($order) The method is forwarded to {@link Query::order}.
 * @method Query limit() limit($limit, $offset=null) The method is forwarded to {@link Query::limit}.
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
		$tags += array
		(
			self::BELONGS_TO => null,
			self::EXTENDING => null,
			self::ID => null,
			self::SCHEMA => null,
			self::ACTIVERECORD_CLASS => null
		);

		if ($tags[self::EXTENDING] && !$tags[self::SCHEMA])
		{
			$extends = $tags[self::EXTENDING];

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
	}

	/**
	 * Handles the _belongs to_ relationship of the model.
	 *
	 * <pre>
	 * $cars->belongs_to(array($drivers, $brands));
	 * # or
	 * $cars->belongs_to(array('drivers', 'brands'));
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
	 * @throws ActiveRecordException if the class of the active record is `ICanBoogie\ActiveRecord`.
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
// 		$getter_name = 'volatile_get_' . \ICanBoogie\singularize($belongs_to_id);
		$getter_name = 'get_' . \ICanBoogie\singularize($belongs_to_id);

		if (!$activerecord_class || $activerecord_class == 'ICanBoogie\ActiveRecord')
		{
			throw new ActiveRecordException('The Active Record class cannot be <code>ICanBoogie\ActiveRecord</code> for a <em>belongs to</em> relationship.');
		}

		$prototype = \ICanBoogie\Prototype::get($activerecord_class);

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
	 * Handles query methods, dynamic filters and scopes.
	 */
	public function __call($method, $arguments)
	{
		if (is_callable(array('ICanBoogie\ActiveRecord\Query', $method))
		|| strpos($method, 'filter_by_') === 0
		|| method_exists($this, 'scope_' . $method))
		{
			$query = new Query($this);

			return call_user_func_array(array($query, $method), $arguments);
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
	protected function volatile_get_id()
	{
		return $this->attributes[self::ID];
	}

	/**
	 * Returns the class of the active records of the model.
	 *
	 * @return string
	 */
	protected function volatile_get_activerecord_class()
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
				$query_records = $this->where(array($primary => array_keys($missing)))->all;

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
			$record = $this->where(array($this->primary => $key))->one;

			if (!$record)
			{
				throw new RecordNotFound
				(
					"Record <q>{$key}</q> does not exists in model <q>{$this->name_unprefixed}</q>.", array($key => null)
				);
			}

			$this->store($record);
		}

		return $record;
	}

	/**
	 * Because records are cached, we need to removed the record from the cache when it is saved,
	 * so that loading the record again returns the updated record, not the one in the cache.
	 */
	public function save(array $properties, $key=null, array $options=array())
	{
		if ($key)
		{
			$this->eliminate($key);
		}

		return parent::save($properties, $key, $options);
	}

	static protected $cached_records;

	/**
	 * Stores a record in the records cache.
	 *
	 * @param ActiveRecord $record The record to store.
	 */
	protected function store(ActiveRecord $record)
	{
		$cache_key = $this->create_cache_key($record->{$this->primary});

		if (!$cache_key || isset(self::$cached_records[$cache_key]))
		{
			return;
		}

		self::$cached_records[$cache_key] = $record;

		Helpers::cache_store($this, $record);
	}

	/**
	 * Retrieves a record from the records cache.
	 *
	 * @param int $key
	 *
	 * @return ActiveRecord|null Returns the active record found in the cache or null if it wasn't
	 * there.
	 */
	protected function retrieve($key)
	{
		$cache_key = $this->create_cache_key($key);

		if (!$cache_key)
		{
			return;
		}

		$record = null;

		if (isset(self::$cached_records[$cache_key]))
		{
			$record = self::$cached_records[$cache_key];
		}
		else
		{
			$record = Helpers::cache_retrieve($this, $key);

			if ($record)
			{
				self::$cached_records[$cache_key] = $record;
			}
		}

		return $record;
	}

	/**
	 * Eliminates an object from the cache.
	 *
	 * @param int $key
	 */
	protected function eliminate($key)
	{
		$cache_key = $this->create_cache_key($key);

		if (!$cache_key)
		{
			return;
		}

		unset(self::$cached_records[$cache_key]);

		Helpers::cache_eliminate($this, $key);
	}

	/**
	 * Creates a unique cache key.
	 *
	 * @param int $key
	 *
	 * @return string A unique cache key.
	 */
	protected function create_cache_key($key)
	{
		return Helpers::create_cache_key($this, $key);
	}

	/**
	 * Checks that the SQL table associated with the model exists.
	 *
	 * @return bool
	 */
	protected function volatile_get_exists()
	{
		return $this->exists();
	}

	/**
	 * Returns the number of records of the model.
	 *
	 * @return int
	 */
	protected function volatile_get_count()
	{
		return $this->count();
	}

	/**
	 * Returns all the records of the model.
	 *
	 * @return array[]ActiveRecord
	 */
	protected function volatile_get_all()
	{
		return $this->all();
	}

	/**
	 * Returns the first record of the model.
	 *
	 * @return ActiveRecord
	 */
	protected function volatile_get_one()
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

		return call_user_func_array(array($this, $callback), $scope_args);
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
		throw new OffsetNotWritable(array($offset, $this));
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
class RecordNotFound extends ActiveRecordException
{
	/**
	 * A key/value array where keys are the identifier of the record, and the value is the result
	 * of finding the record. If the record was found the value is a {@link ActiveRecord}
	 * object, otherwise the `null` value.
	 *
	 * @var array[int]ActiveRecord|null
	 */
	private $records;

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

	public function __get($property)
	{
		switch ($property)
		{
			case 'records': return $this->records;
		}
	}
}

/**
 * Exception thrown when a scope is not defined.
 *
 * @property-read string $scope_name
 * @property-read Model $model
 */
class ScopeNotDefined extends ActiveRecordException
{
	/**
	 * Name of the scope.
	 *
	 * @var string
	 */
	private $scope_name;

	/**
	 * Model on which the scope was invoked.
	 *
	 * @var Model
	 */
	private $model;

	/**
	 * Initializes the {@link $scope_name} and {@link $model} properties.
	 *
	 * @param string $scope_name Name of the scope.
	 * @param Model $model Model on which the scope was invoked.
	 * @param int $code Default to 404.
	 * @param \Exception $previous Previous exception.
	 */
	public function __construct($scope_name, Model $model, $code=500, \Exception $previous)
	{
		$this->scope_name = $scope_name;
		$this->model = $model;

		parent::__construct("Unknown scope <q>{$scope_name}</q> for model <q>{$model->name_unprefixed}</q>.", $code, $previous);
	}

	public function __get($property)
	{
		switch ($property)
		{
			case 'scope_name': return $this->scope_name;
			case 'model': return $this->model;
		}
	}
}