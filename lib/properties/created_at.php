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
 * Implements a `created_at` property.
 *
 * @see DateTimeProperty
 */
trait CreatedAtProperty
{
	/**
	 * The date and time at which the record was created.
	 *
	 * @var string
	 */
	private $created_at;

	/**
	 * Returns the date and time at which the record was created.
	 *
	 * @return \ICanBoogie\DateTime
	 */
	protected function get_created_at()
	{
		return DateTimePropertySupport::datetime_get($this->created_at);
	}

	/**
	 * Sets the date and time at which the record was created.
	 *
	 * @param mixed $value
	 */
	protected function set_created_at($datetime)
	{
		DateTimePropertySupport::datetime_set($this->created_at, $datetime);
	}
}