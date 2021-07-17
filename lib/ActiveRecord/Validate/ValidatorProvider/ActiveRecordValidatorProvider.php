<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\ValidatorProvider;

use ICanBoogie\ActiveRecord\Validate\Validator;
use ICanBoogie\Validate\ValidatorProvider\SimpleValidatorProvider;

/**
 * A validator provider for active record.
 */
class ActiveRecordValidatorProvider extends SimpleValidatorProvider
{
    private static $active_record_validators = [

        'unique' => Validator\Unique::class

    ];

    /**
     * Adds aliases to active record validator classes.
     *
     * @inheritdoc
     */
    public function __construct(array $aliases = [])
    {
        parent::__construct($aliases + self::$active_record_validators);
    }
}
