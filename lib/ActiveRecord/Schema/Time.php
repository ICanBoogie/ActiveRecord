<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Time extends Column
{
    public const CURRENT_TIME = 'CURRENT_TIME';

    /**
     * @param array{
     *     null: bool,
     *     default: ?string,
     *     unique: bool,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['null'],
            $an_array['default'],
            $an_array['unique'],
        );
    }

    public function __construct(
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
    ) {
        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
        );
    }
}
