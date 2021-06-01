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
 * Exception thrown in attempt to set the definition of an already established connection.
 *
 * @property-read string $id The identifier of the connection.
 */
class ConnectionAlreadyEstablished extends \LogicException implements Exception
{
	/**
	 * @uses get_id
	 */
	use AccessorTrait;

	/**
	 * @var string
	 */
	private $id;

	private function get_id(): string
	{
		return $this->id;
	}

	public function __construct(string $id, int $code = 500, \Throwable $previous = null)
	{
		$this->id = $id;

		parent::__construct($this->format_message($id), $code, $previous);
	}

	private function format_message(string $id): string
	{
		return format("Connection already established: %id.", [

			'id' => $id

		]);
	}
}
