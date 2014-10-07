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
 * Exception thrown when the fetch mode of a statement fails to be set.
 */
class UnableToSetFetchMode extends \RuntimeException implements Exception
{
	use \ICanBoogie\PrototypeTrait;

	private $mode;

	protected function get_mode()
	{
		return $this->mode;
	}

	public function __construct($mode, $message=null, $code=500, \Exception $previous=null)
	{
		$this->mode = $mode;

		if (!$message)
		{
			$message = \ICanBoogie\format("Unable to set fetch mode: %mode", [ 'mode' => $mode ]);
		}

		parent::__construct($message, $code, $previous);
	}
}