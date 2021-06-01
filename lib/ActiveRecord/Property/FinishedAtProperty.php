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

use ICanBoogie\DateTime;

/**
 * Implements a `finished_at` property.
 *
 * @see DateTimeProperty
 *
 * @property DateTime $finished_at
 *
 * @codeCoverageIgnore
 */
trait FinishedAtProperty
{
	/**
	 * The date and time at which the record was finished.
	 *
	 * @var mixed
	 */
	private $finished_at;

	/**
	 * Returns the date and time at which the record was finished.
	 */
	protected function get_finished_at(): DateTime
	{
		return DateTimePropertySupport::get($this->finished_at);
	}

	/**
	 * Sets the date and time at which the record was finished.
	 *
	 * @param mixed $datetime
	 */
	protected function set_finished_at($datetime): void
	{
		DateTimePropertySupport::set($this->finished_at, $datetime);
	}
}
