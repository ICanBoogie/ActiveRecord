<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\ActiveRecordCache;

use ArrayIterator;
use ICanBoogie\ActiveRecord;
use IteratorAggregate;

/**
 * Cache records during run time.
 *
 * @implements IteratorAggregate<string, ActiveRecord>
 */
class RuntimeActiveRecordCache extends AbstractActiveRecordCache implements IteratorAggregate
{
	/**
	 * Cached records.
	 *
	 * @var ActiveRecord[]
	 */
	private $records = [];

	/**
	 * @inheritdoc
	 */
	public function store(ActiveRecord $record): void
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
	public function retrieve($key): ?ActiveRecord
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
	public function eliminate($key): void
	{
		unset($this->records[$key]);
	}

	/**
	 * @inheritdoc
	 */
	public function clear(): void
	{
		$this->records = [];
	}

	/**
	 * @inheritdoc
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->records);
	}
}
