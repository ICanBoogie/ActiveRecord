<?php

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
        parent::__construct($this->format_message($errors), previous: $previous);
    }

    private function format_message(ValidationErrors $errors): string
    {
        $message = self::DEFAULT_MESSAGE . "\n";

        foreach ($errors as $attribute => $attribute_errors) {
            // @phpstan-ignore-next-line
            foreach ($attribute_errors as $error) {
                // @phpstan-ignore-next-line
                $message .= "\n- $attribute: $error";
            }
        }

        return $message;
    }
}
