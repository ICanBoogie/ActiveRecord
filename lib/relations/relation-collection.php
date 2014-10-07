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

use ICanBoogie\GetterTrait;
use ICanBoogie\OffsetNotDefined;
use ICanBoogie\OffsetNotWritable;

/**
 * Relation collection of a model.
 *
 * @property-read Model $model The parent model.
 */
class RelationCollection implements \ArrayAccess
{
	use \ICanBoogie\GetterTrait;

	/**
	 * Parent model.
	 *
	 * @var Model
	 */
	protected $model;

	protected function get_model()
	{
		return $this->model;
	}

	/**
	 * Relations.
	 *
	 * @var Relation[]
	 */
	protected $relations;

	/**
	 * Initialize the {@link $model} property.
	 *
	 * @param Model $model The parent model.
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
	}

	public function offsetExists($relation_name)
	{
		return isset($this->relations[$relation_name]);
	}

	public function offsetGet($relation_name)
	{
		if (!$this->offsetExists($relation_name))
		{
			throw new RelationNotDefined($relation_name, $this);
		}

		return $this->relations[$relation_name];
	}

	public function offsetSet($relation_name, $relation)
	{
		throw new OffsetNotWritable([ $relation_name, $this ]);
	}

	public function offsetUnset($relation_name)
	{
		throw new OffsetNotWritable([ $relation_name, $this ]);
	}

	/**
	 * Add a _belongs to_ relation.
	 *
	 * <pre>
	 * $cars->belongs_to([ $drivers, $brands ]);
	 * # or
	 * $cars->belongs_to([ 'drivers', 'brands' ]);
	 * # or
	 * $cars->belongs_to($drivers, $brands);
	 * # or
	 * $cars->belongs_to($drivers)->belongs_to($brands);
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

		$relation = new BelongsToRelation($this->model, $belongs_to);

		$this->relations[$relation->as] = $relation;

		return $this->model;
	}

	/**
	 * Add a one-to-many relation.
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
	 * @return Model
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

				$relation = new HasManyRelation($this->model, $related, $options);

				$this->relations[$relation->as] = $relation;
			}

			return;
		}

		$relation = new HasManyRelation($this->model, $related, $options);

		$this->relations[$relation->as] = $relation;

		return $this->model;
	}
}

/**
 * Exception thrown in attempt to obtain a relation that is not defined.
 *
 * @property-read string $relation_name Name of the undefined relation.
 * @property-read RelationCollection $collection Relation collection.
 */
class RelationNotDefined extends OffsetNotDefined
{
	use \ICanBoogie\GetterTrait;

	/**
	 * Name of the undefined relation.
	 *
	 * @var string
	 */
	private $relation_name;

	protected function get_relation_name()
	{
		return $this->relation_name;
	}

	/**
	 * Relation collection.
	 *
	 * @var RelationCollection
	 */
	private $collection;

	protected function get_collection()
	{
		return $this->collection;
	}

	public function __construct($relation_name, $collection, $code=500, \Exception $previous=null)
	{
		$this->relation_name = $relation_name;
		$this->collection = $collection;

		parent::__construct([ $relation_name, $collection ], $code, $previous);
	}
}