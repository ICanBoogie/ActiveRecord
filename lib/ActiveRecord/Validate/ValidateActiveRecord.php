<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use ICanBoogie\ActiveRecord\Validate\ValidatorProvider\ActiveRecordValidatorProvider;
use ICanBoogie\Validate\Validation;
use ICanBoogie\Validate\ValidationErrors;
use ICanBoogie\Validate\ValidatorProvider;
use ICanBoogie\Validate\ValidatorProvider\BuiltinValidatorProvider;
use ICanBoogie\Validate\ValidatorProvider\ValidatorProviderCollection;

/**
 * Validates an active record.
 */
class ValidateActiveRecord
{
	/**
	 * Validates an active record.
	 *
	 * @param ActiveRecord $record
	 *
	 * @return ValidationErrors|array An array of errors.
	 */
	public function __invoke(ActiveRecord $record)
	{
		$rules = $this->resolve_rules($record);

		if (!$rules)
		{
			return [];
		}

		$validator = $this->create_validator(
			$rules,
			$this->create_validator_provider()
		);

		return $validator->validate($this->create_reader($record));
	}

	/**
	 * Resolves validation rules.
	 *
	 * @param ActiveRecord $record
	 *
	 * @return array
	 */
	protected function resolve_rules(ActiveRecord $record)
	{
		return $record->create_validation_rules();
	}

	/**
	 * Creates validator provider.
	 *
	 * @return ValidatorProvider
	 */
	protected function create_validator_provider()
	{
		return new ValidatorProviderCollection([

			new ActiveRecordValidatorProvider,
			new BuiltinValidatorProvider(),

		]);
	}

	/**
	 * Creates validations.
	 *
	 * @param array $rules
	 * @param callable $validator_provider
	 *
	 * @return Validation
	 */
	protected function create_validator(array $rules, callable $validator_provider = null): Validation
	{
		return new Validation($rules, $validator_provider);
	}

	/**
	 * Creates the value reader for the active record.
	 *
	 * @param ActiveRecord $record
	 *
	 * @return RecordAdapter
	 */
	protected function create_reader(ActiveRecord $record): RecordAdapter
	{
		return new RecordAdapter($record);
	}
}
