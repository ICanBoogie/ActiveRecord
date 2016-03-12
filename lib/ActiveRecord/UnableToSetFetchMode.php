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
 * Exception thrown when the fetch mode of a statement fails to be set.
 *
 * @property-read int $mode Requested fetch mode.
 */
class UnableToSetFetchMode extends \RuntimeException implements Exception
{
	use AccessorTrait;

	/**
	 * @var int
	 */
	private $mode;

	protected function get_mode()
	{
		return $this->mode;
	}

	/**
	 * @param int $mode
	 * @param null $message
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct($mode, $message = null, $code = 500, \Exception $previous = null)
	{
		$this->mode = $mode;

		parent::__construct($message ?: $this->format_message($mode), $code, $previous);
	}

	/**
	 * Formats exception message.
	 *
	 * @param int $mode
	 *
	 * @return string
	 */
	protected function format_message($mode)
	{
		return format("Unable to set fetch mode: %mode", [ 'mode' => $mode ]);
	}
}
