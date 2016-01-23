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
use ICanBoogie\ActiveRecord;
use ICanBoogie\Validate\ValidationErrors;

/**
 * Exception thrown when the validation of a record failed.
 *
 * @property-read ActiveRecord $record
 * @property-read ValidationErrors $errors
 */
class RecordNotValid extends \LogicException implements Exception
{
	use AccessorTrait;

	const DEFAULT_MESSAGE = "The record is not valid.";

	/**
	 * @var ActiveRecord
	 */
	private $record;

	/**
	 * @return ActiveRecord
	 */
	protected function get_record()
	{
		return $this->record;
	}

	/**
	 * @var ValidationErrors
	 */
	private $errors;

	/**
	 * @return ValidationErrors
	 */
	protected function get_errors()
	{
		return $this->errors;
	}

	/**
	 * @param ActiveRecord $record
	 * @param ValidationErrors $errors
	 * @param \Exception|null $previous
	 */
	public function __construct(ActiveRecord $record, ValidationErrors $errors, \Exception $previous = null)
	{
		$this->record = $record;
		$this->errors = $errors;

		parent::__construct($this->format_message($errors), 500, $previous);
	}

	/**
	 * Formats exception message.
	 *
	 * @param ValidationErrors $errors
	 *
	 * @return string
	 */
	protected function format_message(ValidationErrors $errors)
	{
		$message = self::DEFAULT_MESSAGE . "\n";

		foreach ($errors as $attribute => $attribute_errors)
		{
			foreach ($attribute_errors as $error)
			{
				$message .= "\n- $attribute: $error";
			}
		}

		return $message;
	}
}
