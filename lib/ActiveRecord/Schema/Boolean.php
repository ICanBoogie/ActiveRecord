<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Represents a boolean value.
 *
 * The default value is stored as `TRUE` or `FALSE`, which all the supported engines understand.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Boolean extends Column
{
    public const string TRUE = 'TRUE';
    public const string FALSE = 'FALSE';

    /**
     * @param array{
     *     null: bool,
     *     default: self::TRUE|self::FALSE|null,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['null'],
            $an_array['default'] === null ? null : $an_array['default'] === self::TRUE,
        );
    }

    public function __construct(
        bool $null = false,
        ?bool $default = null,
    ) {
        parent::__construct(
            null: $null,
            default: match ($default) {
                null => null,
                true => self::TRUE,
                false => self::FALSE,
            },
        );
    }
}
