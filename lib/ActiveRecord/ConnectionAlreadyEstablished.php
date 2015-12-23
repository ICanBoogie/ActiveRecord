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
 * Exception thrown in attempt to set the definition of an already established connection.
 *
 * @property-read string $id The identifier of the connection.
 */
class ConnectionAlreadyEstablished extends \LogicException implements Exception
{
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @return string
	 */
	protected function get_id()
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct($id, $code = 500, \Exception $previous = null)
	{
		$this->id = $id;

		parent::__construct($this->format_message($id), $code, $previous);
	}

	/**
	 * Formats exception message.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function format_message($id)
	{
		return \ICanBoogie\format("Connection already established: %id.", [

			'id' => $id

		]);
	}
}
