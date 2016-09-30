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
 * Implements a `started_at` property.
 *
 * @see DateTimeProperty
 *
 * @property ImmutableDateTime $started_at
 *
 * @codeCoverageIgnore
 */
trait StartedAtProperty
{
	/**
	 * The date and time at which the record was started.
	 *
	 * @var mixed
	 */
	private $started_at;

	/**
	 * Returns the date and time at which the record was started.
	 *
	 * @return ImmutableDateTime
	 */
	protected function get_started_at()
	{
		return DateTimePropertySupport::get($this->started_at);
	}

	/**
	 * Sets the date and time at which the record was started.
	 *
	 * @param mixed $datetime
	 */
	protected function set_started_at($datetime)
	{
		DateTimePropertySupport::set($this->started_at, $datetime);
	}
}
