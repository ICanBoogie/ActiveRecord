<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Boolean extends Integer
{
    /**
     * @param array{
     *     null: bool,
     * } $an_array
     *
     * @return object
     */
    public static function __set_state(array $an_array): object
    {
        return new self(
            $an_array['null'],
        );
    }

    public function __construct(
        bool $null = false,
    ) {
        parent::__construct(
            size: parent::SIZE_TINY,
            unsigned: true,
            null: $null,
        );
    }
}
