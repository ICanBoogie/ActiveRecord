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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord;
use ICanBoogie\Prototype;

/**
 * Representation of a relation.
 *
 * @property-read Model $parent The parent model of the relation.
 * @property-read Model $related The related model of the relation.
 * @property-read string $as The name of the relation.
 * @property-read string $local_key The local key.
 * @property-read string $foreign_key The foreign key.
 */
abstract class Relation
{
	use AccessorTrait;

	/**
	 * The parent model of the relation.
	 *
	 * @var Model
	 * @uses get_parent
	 */
	private $parent;

	private function get_parent(): Model
	{
		return $this->parent;
	}

	/**
	 * The related model of the relation.
	 *
	 * @var Model
	 * @uses get_related
	 */
	private $related;

	private function get_related(): Model
	{
		$related = $this->related;

		if ($related instanceof Model)
		{
			return $related;
		}

		/* @var $related string */

		return $this->related = $this->parent->models[$related];
	}

	/**
	 * The name of the relation.
	 *
	 * @var string
	 * @uses get_as
	 */
	private $as;

	private function get_as(): string
	{
		return $this->as;
	}

	/**
	 * Local key. Default: The parent model's primary key.
	 *
	 * @var string
	 * @uses get_local_key
	 */
	private $local_key;

	private function get_local_key(): string
	{
		return $this->local_key;
	}

	/**
	 * Foreign key. Default: The parent model's primary key.
	 *
	 * @var string
	 * @uses get_foreign_key
	 */
	private $foreign_key;

	private function get_foreign_key(): string
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
	 */
	public function __construct(Model $parent, $related, array $options = [])
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
	 * @return mixed
	 */
	abstract public function __invoke(ActiveRecord $record);

	/**
	 * Add a getter for the relation to the prototype.
	 *
	 * @param Prototype $prototype The active record prototype.
	 * @param string $property The name of the property.
	 */
	protected function alter_prototype(Prototype $prototype, string $property): void
	{
		$prototype["get_$property"] = $this;
	}

	/**
	 * Resolve the active record class name from the specified model.
	 *
	 * @param Model $model
	 *
	 * @throws ActiveRecordClassNotValid
	 *
	 * @return string
	 */
	protected function resolve_activerecord_class(Model $model): string
	{
		$activerecord_class = $model->activerecord_class;

		if (!$activerecord_class || $activerecord_class == ActiveRecord::class)
		{
			throw new ActiveRecordClassNotValid($activerecord_class, 'The Active Record class cannot be <code>ICanBoogie\ActiveRecord</code> for a relationship.');
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
	protected function resolve_property_name($related): string
	{
		$related_id = $related instanceof Model ? $related->id : $related;
		$parts = \explode('.', $related_id);

		return \array_pop($parts);
	}

	/**
	 * Resolve the related model.
	 *
	 * @return Model
	 */
	protected function resolve_related(): Model
	{
		$related = $this->related;

		if ($related instanceof Model)
		{
			return $related;
		}

		/* @var $related string */

		return $this->related = $this->parent->models[$related];
	}
}
