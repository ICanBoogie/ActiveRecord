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

use ICanBoogie\DateTime;

/**
 * Provides support for datetime properties.
 */
class DateTimePropertySupport
{
	/**
	 * Sets the a datetime is a property.
	 *
	 * @param mixed $property Reference to the property to set.
	 * @param mixed $datetime Date and time.
	 */
	static public function datetime_set(&$property, $datetime)
	{
		$property = $datetime;
	}

	/**
	 * Returns the {@link DateTime} instance of a property.
	 *
	 * @param mixed $property Reference to the property to return.
	 *
	 * @return DateTime The function always return a {@link DateTime} instance.
	 */
	static public function datetime_get(&$property)
	{
		if ($property instanceof DateTime)
		{
			return $property;
		}

		return $property = $property === null ? DateTime::none() : new DateTime($property, 'utc');
	}
}

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
		return DateTimePropertySupport::datetime_get($this->datetime);
	}

	/**
	 * Sets the date and time at which the record was created.
	 *
	 * @param mixed $value
	 */
	protected function set_datetime($datetime)
	{
		DateTimePropertySupport::datetime_set($this->datetime, $datetime);
	}
}