<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Represents an auto-incrementing integer.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Serial extends Integer
{
    /**
     * @param array{
     *     size: self::SIZE_*,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(
            $an_array['size'],
        );
    }

    public function __construct(
        int $size = parent::SIZE_REGULAR,
    ){
        parent::__construct(
            size: $size,
            unsigned: true,
            serial: true,
            unique: true
        );
    }
}
