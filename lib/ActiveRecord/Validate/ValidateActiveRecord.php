<?php

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
     * @return ValidationErrors|array An array of errors.
     */
    public function __invoke(ActiveRecord $record) // @phpstan-ignore-line
    {
        $rules = $this->resolve_rules($record);

        if (!$rules) {
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
     * @return array<string, mixed>
     */
    protected function resolve_rules(ActiveRecord $record): array
    {
        return $record->create_validation_rules();
    }

    /**
     * Creates validator provider.
     */
    protected function create_validator_provider(): ValidatorProvider
    {
        return new ValidatorProviderCollection([

            new ActiveRecordValidatorProvider(),
            new BuiltinValidatorProvider(),

        ]);
    }

    /**
     * Creates validations.
     *
     * @param array<string, mixed> $rules
     */
    protected function create_validator(array $rules, ?callable $validator_provider = null): Validation
    {
        return new Validation($rules, $validator_provider);
    }

    /**
     * Creates the value reader for the active record.
     */
    protected function create_reader(ActiveRecord $record): RecordAdapter
    {
        return new RecordAdapter($record);
    }
}
