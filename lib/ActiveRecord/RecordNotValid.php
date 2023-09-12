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

use ICanBoogie\ActiveRecord;
use ICanBoogie\Validate\ValidationErrors;
use LogicException;
use Throwable;

/**
 * Exception thrown when the validation of a record failed.
 */
class RecordNotValid extends LogicException implements Exception
{
    public const DEFAULT_MESSAGE = "The record is not valid.";

    public function __construct(
        public readonly ActiveRecord $record,
        public readonly ValidationErrors $errors,
        Throwable $previous = null
    ) {
        parent::__construct($this->format_message($errors), 0, $previous);
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
