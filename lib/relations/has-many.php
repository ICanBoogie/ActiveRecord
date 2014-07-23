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
 * Representation of the one-to-many relation.
 */
class HasManyRelation
{
	/**
	 * Local key. Default: The parent model's primary key.
	 *
	 * @var string
	 */
	protected $local_key;

	/**
	 * Foreign key. Default: The parent model's primary key.
	 *
	 * @var string
	 */
	protected $foreign_key;

	/**
	 * The parent model of the relation.
	 *
	 * @var Model
	 */
	protected $parent;

	/**
	 * The related model of the relation.
	 *
	 * @var Model
	 */
	protected $related;

	/**
	 * Initialize the {@link $parent}, {@link $related} and {@link $primary} properties.
	 *
	 * @param Model $parent The parent model of the relation.
	 * @param Model|string $related The related model of the relation. Can be specified using its
	 * instance or its identifier.
	 * @param array $options the following options are available:
	 *
	 * - `local_key`: The name of the local key. Default: The parent model's primary key.
	 * - `foreign_key`: The name of the foreign key. Default: The parent model's primary key.
	 * - `as`: The name of the magic property to add to the prototype. Default: a plural name
	 * resolved from the foreign model's id.
	 *
	 * @throws ActiveRecordException if the active record class of the parent model is {@link ActiveRecord}.
	 */
	public function __construct(Model $parent, $related, array $options=[])
	{
		$options += [

			'local_key' => $parent->primary,
			'foreign_key' => $parent->primary,
			'as' => null

		];

		$this->parent = $parent;
		$this->related = $related;
		$this->local_key = $options['local_key'];
		$this->foreign_key = $options['foreign_key'];

		$activerecord_class = $this->resolve_activerecord_class($parent);
		$prototype = Prototype::from($activerecord_class);
		$getter = 'get_' . ($options['as'] ?: $this->resolve_property_name($related));
		$prototype[$getter] = $this;
	}

	/**
	 * Create a query to retrieve the records that belong to the specified record.
	 *
	 * @param ActiveRecord $record
	 *
	 * @return Query
	 */
	public function __invoke(ActiveRecord $record)
	{
		$related = $this->related;

		if (!($related instanceof Model))
		{
			$this->related = $related = get_model($related);
		}

		return $related->where([ $this->foreign_key => $record->{ $this->local_key }]);
	}

	/**
	 * Resolve the active record class name from the specified model.
	 *
	 * @param Model $model
	 *
	 * @throws ActiveRecordException if the class is {@link ActiveRecord}.
	 *
	 * @return string
	 */
	private function resolve_activerecord_class(Model $model)
	{
		$activerecord_class = $model->activerecord_class;

		if (!$activerecord_class || $activerecord_class == 'ICanBoogie\ActiveRecord')
		{
			throw new ActiveRecordException('The Active Record class cannot be <code>ICanBoogie\ActiveRecord</code> for a relationship.');
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
	private function resolve_property_name($related)
	{
		$model_id = $related instanceof Model ? $related->id : $related;
		$parts = explode('.', $model_id);

		$property_name = array_pop($parts);
		$property_name = \ICanBoogie\pluralize($property_name);

		return $property_name;
	}
}