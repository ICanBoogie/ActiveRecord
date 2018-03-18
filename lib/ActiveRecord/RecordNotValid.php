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
	 * @uses get_record
	 */
	private $record;

	private function get_record(): ActiveRecord
	{
		return $this->record;
	}

	/**
	 * @var ValidationErrors
	 * @uses get_errors
	 */
	private $errors;

	private function get_errors(): ValidationErrors
	{
		return $this->errors;
	}

	public function __construct(ActiveRecord $record, ValidationErrors $errors, \Throwable $previous = null)
	{
		$this->record = $record;
		$this->errors = $errors;

		parent::__construct($this->format_message($errors), 500, $previous);
	}

	private function format_message(ValidationErrors $errors): string
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
