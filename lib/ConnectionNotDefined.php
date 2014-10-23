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
 * Exception thrown in attempt to obtain a connection that is not defined.
 *
 * @property-read string $id The identifier of the connection.
 */
class ConnectionNotDefined extends \LogicException implements Exception
{
	use \ICanBoogie\GetterTrait;

	private $id;

	protected function get_id()
	{
		return $this->id;
	}

	public function __construct($id, $code=500, \Exception $previous=null)
	{
		$this->id = $id;

		parent::__construct(\ICanBoogie\format("Connection not defined: %id.", [

			'id' => $id

		]), $code, $previous);
	}
}
