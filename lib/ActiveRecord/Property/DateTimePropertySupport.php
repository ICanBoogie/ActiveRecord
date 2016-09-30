<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Property;

use ICanBoogie\ImmutableDateTime;

/**
 * Provides support for datetime properties.
 */
class DateTimePropertySupport
{
	/**
	 * Sets the datetime in a property.
	 *
	 * @param mixed $property Reference to the property to set.
	 * @param mixed $datetime Date and time.
	 */
	static public function set(&$property, $datetime)
	{
		$property = $datetime === 'now' ? ImmutableDateTime::now() : $datetime;
	}

	/**
	 * Returns the {@link DateTime} instance of a property.
	 *
	 * @param mixed $property Reference to the property to return.
	 *
	 * @return ImmutableDateTime The function always return a {@link ImmutableDateTime} instance.
	 */
	static public function get(&$property)
	{
		if ($property instanceof ImmutableDateTime)
		{
			return $property;
		}

		return $property = $property === null
			? ImmutableDateTime::none()
			: ImmutableDateTime::from($property, 'utc');
	}
}
