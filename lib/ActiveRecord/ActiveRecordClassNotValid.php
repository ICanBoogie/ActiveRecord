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
	 * @uses get_class
	 */
	private $class;

	private function get_class(): string
	{
		return $this->class;
	}

	/**
	 * @param mixed $class
	 * @param string|null $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct($class, string $message = null, int $code = 500, \Throwable $previous = null)
	{
		$this->class = $class;

		parent::__construct($message ?: $this->format_message($class), $code, $previous);
	}

	private function format_message(string $class): string
	{
		return format("ActiveRecord class is not valid: %class", [

			'class' => $class

		]);
	}
}
