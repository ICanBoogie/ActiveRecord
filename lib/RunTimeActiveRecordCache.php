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
class RunTimeActiveRecordCache extends ActiveRecordCache implements \IteratorAggregate
{
	/**
	 * Cached records.
	 *
	 * @var ActiveRecord[]
	 */
	protected $records = [];

	public function store(ActiveRecord $record)
	{
		$key = $record->{ $this->model->primary };

		if (!$key || isset($this->records[$key]))
		{
			return;
		}

		$this->records[$key] = $record;
	}

	public function retrieve($key)
	{
		if (empty($this->records[$key]))
		{
			return null;
		}

		return $this->records[$key];
	}

	public function eliminate($key)
	{
		unset($this->records[$key]);
	}

	public function clear()
	{
		$this->records = [];
	}

	public function  getIterator()
	{
		return new \ArrayIterator($this->records);
	}
}
