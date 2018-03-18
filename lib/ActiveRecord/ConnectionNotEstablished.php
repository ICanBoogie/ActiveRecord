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
	 * @uses get_id
	 */
	private $id;

	private function get_id(): string
	{
		return $this->id;
	}

	public function __construct(string $id, string $message, int $code = 500, \Throwable $previous = null)
	{
		$this->id = $id;

		parent::__construct($message, $code, $previous);
	}
}
