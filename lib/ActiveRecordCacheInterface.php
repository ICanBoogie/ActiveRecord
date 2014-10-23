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
 * Interface for ActiveRecord cache.
 */
interface ActiveRecordCacheInterface
{
	/**
	 * Stores an {@link ActiveRecord} instance in the cache.
	 *
	 * @param ActiveRecord $record
	 */
	public function store(ActiveRecord $record);

	/**
	 * Retrieves an {@link ActiveRecord} instance from the cache.
	 *
	 * @param int $key
	 *
	 * @return ActiveRecord|null
	 */
	public function retrieve($key);

	/**
	 * Eliminates an {@link ActiveRecord} instance from the cache.
	 *
	 * @param int $key
	 */
	public function eliminate($key);

	/**
	 * Clears the cache.
	 */
	public function clear();
}
