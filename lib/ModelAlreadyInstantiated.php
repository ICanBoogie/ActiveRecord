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
 * Exception thrown in attempt to set/unset the definition of an already instantiated model.
 */
class ModelAlreadyInstantiated extends \LogicException implements Exception
{
	public function __construct($id, $code=500, \Exception $previous=null)
	{
		parent::__construct("Model already instanciated: $id.", $code, $previous);
	}
}