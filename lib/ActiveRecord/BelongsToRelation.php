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

use function ICanBoogie\singularize;

/**
 * Representation of the one-to-one relation.
 */
class BelongsToRelation extends Relation
{
	/**
	 * @param Model $parent
	 * @param Model|string $related
	 * @param array $options
	 */
	public function __construct(Model $parent, $related, array $options = [])
	{
		if (empty($options['local_key']) || empty($options['foreign_key']))
		{
			if (!$related instanceof Model)
			{
				$related = $parent->models[$related];
			}

			$options += [

				'local_key' => $related->primary,
				'foreign_key' => $related->primary

			];
		}

		parent::__construct($parent, $related, $options);
	}

	/**
	 * @inheritdoc
	 */
	public function __invoke(ActiveRecord $record): ActiveRecord
	{
		$key = $record->{ $this->local_key };

		if (!$key)
		{
			throw new \LogicException("Unable to establish relation, primary key is empty.");
		}

		return $this->resolve_related()[$key];
	}

	/**
	 * Adds a setter for the property to update the local key.
	 *
	 * @inheritdoc
	 */
	protected function alter_prototype(Prototype $prototype, string $property): void
	{
		parent::alter_prototype($prototype, $property);

		$prototype["set_$property"] = function(ActiveRecord $record, ActiveRecord $related) {

			$record->{ $this->local_key } = $related->{ $this->foreign_key };

		};
	}

	/**
	 * @inheritdoc
	 */
	protected function resolve_property_name($related): string
	{
		return singularize(parent::resolve_property_name($related));
	}
}
