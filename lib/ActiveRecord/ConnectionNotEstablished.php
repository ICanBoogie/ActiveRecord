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
 * Exception thrown when a connection cannot be established.
 *
 * @property-read string $id The identifier of the connection.
 */
class ConnectionNotEstablished extends \RuntimeException implements Exception
{
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $id;

	protected function get_id()
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 * @param string $message
	 * @param int $code
	 * @param \Exception|null $previous
	 */
	public function __construct($id, $message, $code = 500, \Exception $previous = null)
	{
		$this->id = $id;

		parent::__construct($message, $code, $previous);
	}
}
