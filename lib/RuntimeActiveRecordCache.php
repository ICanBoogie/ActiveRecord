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
 * Cache records during run time.
 */
class RuntimeActiveRecordCache extends ActiveRecordCacheBase implements \IteratorAggregate
{
	/**
	 * Cached records.
	 *
	 * @var ActiveRecord[]
	 */
	protected $records = [];

	/**
	 * @inheritdoc
	 */
	public function store(ActiveRecord $record)
	{
		$key = $record->{ $this->model->primary };

		if (!$key || isset($this->records[$key]))
		{
			return;
		}

		$this->records[$key] = $record;
	}

	/**
	 * @inheritdoc
	 */
	public function retrieve($key)
	{
		if (empty($this->records[$key]))
		{
			return null;
		}

		return $this->records[$key];
	}

	/**
	 * @inheritdoc
	 */
	public function eliminate($key)
	{
		unset($this->records[$key]);
	}

	/**
	 * @inheritdoc
	 */
	public function clear()
	{
		$this->records = [];
	}

	/**
	 * @inheritdoc
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->records);
	}
}
