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
use LogicException;
use Throwable;

/**
 * Exception thrown when the validation of a record failed.
 *
 * @property-read ActiveRecord $record
 * @property-read ValidationErrors $errors
 */
class RecordNotValid extends LogicException implements Exception
{
    /**
     * @uses get_record
     * @uses get_errors
     */
    use AccessorTrait;

    public const DEFAULT_MESSAGE = "The record is not valid.";

    private function get_record(): ActiveRecord
    {
        return $this->record;
    }

    private function get_errors(): ValidationErrors
    {
        return $this->errors;
    }

    public function __construct(
        private ActiveRecord $record,
        private ValidationErrors $errors,
        Throwable $previous = null
    ) {
        parent::__construct($this->format_message($errors), 500, $previous);
    }

    private function format_message(ValidationErrors $errors): string
    {
        $message = self::DEFAULT_MESSAGE . "\n";

        foreach ($errors as $attribute => $attribute_errors) {
            foreach ($attribute_errors as $error) {
                $message .= "\n- $attribute: $error";
            }
        }

        return $message;
    }
}
