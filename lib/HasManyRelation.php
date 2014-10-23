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

/**
 * Representation of the one-to-many relation.
 */
class HasManyRelation extends Relation
{
	/**
	 * Create a query to retrieve the records that belong to the specified record.
	 *
	 * @param ActiveRecord $record
	 *
	 * @return Query
	 */
	public function __invoke(ActiveRecord $record)
	{
		return $this
		->resolve_related()
		->where([ $this->foreign_key => $record->{ $this->local_key }]);
	}

	protected function resolve_property_name($related)
	{
		return \ICanBoogie\pluralize(parent::resolve_property_name($related));
	}
}
