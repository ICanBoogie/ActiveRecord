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
 * Exception thrown when the ActiveRecord class is not valid.
 *
 * @package ICanBoogie\ActiveRecord
 */
class ActiveRecordClassNotValid extends \LogicException implements Exception
{
	use AccessorTrait;

	private $class;

	protected function get_class()
	{
		return $this->class;
	}

	public function __construct($class, $message = null, $code = 500, \Exception $previous = null)
	{
		$this->class = $class;

		if (!$message)
		{
			$message = \ICanBoogie\format("ActiveRecord class is not valid: %class", [

				'class' => $class

			]);
		}

		parent::__construct($message, $code, $previous);
	}
}
