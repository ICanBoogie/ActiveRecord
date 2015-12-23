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

/**
 * Abstract root class for an active records cache.
 */
abstract class ActiveRecordCacheBase implements ActiveRecordCache
{
	/**
	 * Model using the cache.
	 *
	 * @var Model
	 */
	protected $model;

	/**
	 * @param Model $model
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
	}
}
