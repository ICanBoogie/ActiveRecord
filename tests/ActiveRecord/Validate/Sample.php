<?php

namespace ICanBoogie\ActiveRecord\Validate;

use ICanBoogie\ActiveRecord;

class Sample extends ActiveRecord
{
	const MODEL_ID = 'model';

	public $email;

	public function create_validation_rules(): array
	{
		return [

			'email' => 'required|email'

		];
	}
}
