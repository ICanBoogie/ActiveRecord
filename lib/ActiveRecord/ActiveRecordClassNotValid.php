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

use function ICanBoogie\format;

/**
 * Exception thrown when the ActiveRecord class is not valid.
 *
 * @property-read string $class
 */
class ActiveRecordClassNotValid extends \LogicException implements Exception
{
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $class;

	/**
	 * @return string
	 */
	protected function get_class()
	{
		return $this->class;
	}

	/**
	 * @param string $class
	 * @param string|null $message
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct($class, $message = null, $code = 500, \Exception $previous = null)
	{
		$this->class = $class;

		parent::__construct($message ?: $this->format_message($class), $code, $previous);
	}

	/**
	 * Formats exception message.
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	protected function format_message($class)
	{
		return format("ActiveRecord class is not valid: %class", [

			'class' => $class

		]);
	}
}
