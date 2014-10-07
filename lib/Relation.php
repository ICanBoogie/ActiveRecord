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
use ICanBoogie\Prototype;

/**
 * Representation of a relation.
 *
 * @property-read Model $parent The parent model of the ralation.
 * @property-read Model $related The related model of the relation.
 * @property-read string $as The name of the relation.
 * @property-read string $local_key The local key.
 * @property-read string $foreign_key The foreign key.
 */
abstract class Relation
{
	use \ICanBoogie\GetterTrait;

	/**
	 * The parent model of the relation.
	 *
	 * @var Model
	 */
	protected $parent;

	protected function get_parent()
	{
		return $this->parent;
	}

	/**
	 * The related model of the relation.
	 *
	 * @var Model
	 */
	protected $related;

	protected function get_related()
	{
		return $this->related;
	}

	/**
	 * The name of the relation.
	 *
	 * @var string
	 */
	protected $as;

	protected function get_as()
	{
		return $this->as;
	}

	/**
	 * Local key. Default: The parent model's primary key.
	 *
	 * @var string
	 */
	protected $local_key;

	protected function get_local_key()
	{
		return $this->local_key;
	}

	/**
	 * Foreign key. Default: The parent model's primary key.
	 *
	 * @var string
	 */
	protected $foreign_key;

	protected function get_foreign_key()
	{
		return $this->foreign_key;
	}

	/**
	 * Initialize the {@link $parent}, {@link $related}, {@link $as}, {@link $local_key}, and
	 * {@link $foreign_key} properties.
	 *
	 * @param Model $parent The parent model of the relation.
	 * @param Model|string $related The related model of the relation. Can be specified using its
	 * instance or its identifier.
	 * @param array $options the following options are available:
	 *
	 * - `as`: The name of the magic property to add to the prototype. Default: a plural name
	 * resolved from the foreign model's id.
	 * - `local_key`: The name of the local key. Default: The parent model's primary key.
	 * - `foreign_key`: The name of the foreign key. Default: The parent model's primary key.
	 *
	 * @throws ActiveRecordException if the active record class of the parent model
	 * is {@link ActiveRecord}.
	 */
	public function __construct(Model $parent, $related, array $options=[])
	{
		$options += [

			'as' => null,
			'local_key' => $parent->primary,
			'foreign_key' => $parent->primary

		];

		$this->parent = $parent;
		$this->related = $related;
		$this->as = $options['as'] ?: $this->resolve_property_name($related);
		$this->local_key = $options['local_key'];
		$this->foreign_key = $options['foreign_key'];

		$activerecord_class = $this->resolve_activerecord_class($parent);
		$prototype = Prototype::from($activerecord_class);

		$this->alter_prototype($prototype, $this->as);
	}

	/**
	 * Create a query with the relation.
	 *
	 * @param ActiveRecord $record
	 *
	 * @return Query
	 */
	abstract public function __invoke(ActiveRecord $record);

	/**
	 * Add a getter for the relation to the prototype.
	 *
	 * @param Prototype $prototype The activerecord prototype.
	 * @param string $as The name of the property.
	 */
	protected function alter_prototype(Prototype $prototype, $property)
	{
		$prototype["get_$property"] = $this;
	}

	/**
	 * Resolve the active record class name from the specified model.
	 *
	 * @param Model $model
	 *
	 * @throws \LogicException if the class is {@link ActiveRecord}.
	 *
	 * @return string
	 */
	protected function resolve_activerecord_class(Model $model)
	{
		$activerecord_class = $model->activerecord_class;

		if (!$activerecord_class || $activerecord_class == 'ICanBoogie\ActiveRecord')
		{
			throw new \LogicException('The Active Record class cannot be <code>ICanBoogie\ActiveRecord</code> for a relationship.');
		}

		return $activerecord_class;
	}

	/**
	 * Resolve the property name from the related model.
	 *
	 * @param Model|string $related The related model of the relation.
	 *
	 * @return string
	 */
	protected function resolve_property_name($related)
	{
		$related_id = $related instanceof Model ? $related->id : $related;
		$parts = explode('.', $related_id);

		return array_pop($parts);
	}

	/**
	 * Resolve the related model.
	 *
	 * @return Model
	 */
	protected function resolve_related()
	{
		$related = $this->related;

		if (!($related instanceof Model))
		{
			$this->related = $related = ActiveRecord\get_model($related);
		}

		return $related;
	}
}