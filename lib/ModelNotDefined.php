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
 * Exception thrown when a requested model is not defined.
 */
class ModelNotDefined extends \LogicException implements Exception
{
	public function __construct($id, $code=500, \Exception $previous=null)
	{
		parent::__construct("Model not defined: $id.", $code, $previous);
	}
}