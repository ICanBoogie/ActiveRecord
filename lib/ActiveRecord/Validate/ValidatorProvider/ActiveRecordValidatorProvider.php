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
    private const ACTIVE_RECORD_VALIDATORS = [

        'unique' => Validator\Unique::class

    ];

    /**
     * Adds aliases to active record validator classes.
     *
     * @inheritdoc
     */
    public function __construct(array $aliases = [])
    {
        parent::__construct($aliases + self::ACTIVE_RECORD_VALIDATORS);
    }
}
