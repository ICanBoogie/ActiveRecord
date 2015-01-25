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
 * Implements a `datetime` property.
 */
trait DateTimeProperty
{
	/**
	 * The date and time at which the record was created.
	 *
	 * @var string
	 */
	private $datetime;

	/**
	 * Returns the date and time at which the record was created.
	 *
	 * @return \ICanBoogie\DateTime
	 */
	protected function get_datetime()
	{
		return DateTimePropertySupport::get($this->datetime);
	}

	/**
	 * Sets the date and time at which the record was created.
	 *
	 * @param mixed $datetime
	 */
	protected function set_datetime($datetime)
	{
		DateTimePropertySupport::set($this->datetime, $datetime);
	}
}
