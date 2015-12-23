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

use ICanBoogie\Accessor\AccessorTrait;

/**
 * Exception thrown when there is no driver defined for a given driver name.
 *
 * @property-read string $driver_name
 */
class DriverNotDefined extends \LogicException implements Exception
{
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $driver_name;

	/**
	 * @return string
	 */
	protected function get_driver_name()
	{
		return $this->driver_name;
	}

	/**
	 * @param string $driver_name
	 * @param string|null $message
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct($driver_name, $message = null, $code = 500, \Exception $previous = null)
	{
		$this->driver_name = $driver_name;

		parent::__construct($message ?: $this->format_message($driver_name), $code, $previous);
	}

	/**
	 * Formats exception message.
	 *
	 * @param string $driver_name
	 *
	 * @return string
	 */
	protected function format_message($driver_name)
	{
		return "Driver not defined for: $driver_name.";
	}
}
