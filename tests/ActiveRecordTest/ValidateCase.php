<?php

namespace ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Validate test case.
 *
 * @property-read string $timezone.
 */
class ValidateCase extends ActiveRecord
{
	/**
	 * @var int
	 */
	private $id;

	/**
	 * @return int
	 */
	protected function get_id()
	{
		return $this->id;
	}

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $email;

	/**
	 * @var string
	 */
	private $timezone = 'Europe\Pas';

	/**
	 * @return string
	 */
	protected function get_timezone()
	{
		return $this->timezone;
	}

	/**
	 * @inheritdoc
	 */
	public function create_validation_rules(): array
	{
		return parent::create_validation_rules() + [

			'name' => 'required|min-length:3',
			'email' => 'required|email|unique',
			'timezone' => 'required|timezone'

		];
	}
}
