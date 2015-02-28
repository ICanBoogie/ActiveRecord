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
use ICanBoogie\OffsetNotDefined;

/**
 * Exception thrown in attempt to obtain a relation that is not defined.
 *
 * @property-read string $relation_name Name of the undefined relation.
 * @property-read RelationCollection $collection Relation collection.
 */
class RelationNotDefined extends OffsetNotDefined implements Exception
{
	use AccessorTrait;

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

	public function __construct($relation_name, RelationCollection $collection, $code = 500, \Exception $previous = null)
	{
		$this->relation_name = $relation_name;
		$this->collection = $collection;

		parent::__construct([ $relation_name, $collection ], $code, $previous);
	}
}
