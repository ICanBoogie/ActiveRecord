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
 * Representation of the one-to-one relation.
 */
class BelongsToRelation extends Relation
{
	public function __construct(Model $parent, $related, array $options = [])
	{
		if (empty($options['local_key']) || empty($options['foreign_key']))
		{
			if (!($related instanceof Model))
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

	public function __invoke(ActiveRecord $record)
	{
		$key = $record->{ $this->local_key };

		if (!$key)
		{
			return null;
		}

		return $this->resolve_related()[$key];
	}

	protected function alter_prototype(Prototype $prototype, $property)
	{
		parent::alter_prototype($prototype, $property);

		$prototype["set_$property"] = function(ActiveRecord $record, ActiveRecord $related) {

			$record->{ $this->local_key } = $related->{ $this->foreign_key };

		};
	}

	protected function resolve_property_name($related)
	{
		return \ICanBoogie\singularize(parent::resolve_property_name($related));
	}
}