<?php

namespace ICanBoogie\ActiveRecord\Validate\ValidatorProvider;

use ICanBoogie\ActiveRecord\Validate\Validator;
use ICanBoogie\Validate\ValidatorProvider\SimpleValidatorProvider;

/**
 * A validator provider for active record.
 */
class ActiveRecordValidatorProvider extends SimpleValidatorProvider
{
    /**
     * @var array<string, class-string>
     */
    private static array $active_record_validators = [

        'unique' => Validator\Unique::class

    ];

    /**
     * Adds aliases to active record validator classes.
     *
     * @param array<string, class-string> $aliases
     *
     * @inheritdoc
     */
    public function __construct(array $aliases = [])
    {
        parent::__construct($aliases + self::$active_record_validators);
    }
}
