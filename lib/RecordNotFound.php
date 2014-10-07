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
 * Exception thrown when an active record cannot be found.
 *
 * @property-read \ICanBoogie\ActiveRecord[] $records
 */
class RecordNotFound extends \LogicException implements Exception
{
	use \ICanBoogie\GetterTrait;

	/**
	 * A key/value array where keys are the identifier of the record, and the value is the result
	 * of finding the record. If the record was found the value is a {@link \ICanBoogie\ActiveRecord}
	 * object, otherwise the `null` value.
	 *
	 * @var \ICanBoogie\ActiveRecord[]
	 */
	private $records;

	protected function get_records()
	{
		return $this->records;
	}

	/**
	 * Initializes the {@link $records} property.
	 *
	 * @param string $message
	 * @param array $records
	 * @param int $code Defaults to 404.
	 * @param \Exception $previous Previous exception.
	 */
	public function __construct($message, array $records=[], $code=404, \Exception $previous=null)
	{
		$this->records = $records;

		parent::__construct($message, $code, $previous);
	}
}