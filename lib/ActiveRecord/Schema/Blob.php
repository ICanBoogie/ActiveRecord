<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Blob extends Column
{
    /**
     * A `TEXT`` with a maximum length of 255 characters.
     */
    public const SIZE_TINY = 'TINY';

    /**
     * A `TEXT`` with a maximum length of 65535 characters.
     */
    public const SIZE_REGULAR = 'REGULAR';

    /**
     * A `TEXT`` with a maximum length of 16777215 characters.
     */
    public const SIZE_MEDIUM = 'MEDIUM';

    /**
     * A `TEXT`` with a maximum length of 4294967295 characters.
     */
    public const SIZE_LONG = 'LONG';

    /**
     * @param array{
     *     size: self::SIZE_*,
     *     null: bool,
     *     default: ?string,
     *     unique: bool,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['size'],
            $an_array['null'],
            $an_array['default'],
            $an_array['unique'],
        );
    }

    /**
     * @param self::SIZE_* $size
     */
    public function __construct(
        public readonly string $size = self::SIZE_REGULAR,
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
