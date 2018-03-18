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
	 * @uses get_relation_name
	 */
	private $relation_name;

	private function get_relation_name(): string
	{
		return $this->relation_name;
	}

	/**
	 * Relation collection.
	 *
	 * @var RelationCollection
	 * @uses get_collection
	 */
	private $collection;

	private function get_collection(): RelationCollection
	{
		return $this->collection;
	}

	public function __construct(string $relation_name, RelationCollection $collection, int $code = 500, \Throwable $previous = null)
	{
		$this->relation_name = $relation_name;
		$this->collection = $collection;

		parent::__construct([ $relation_name, $collection ], $code, $previous);
	}
}
