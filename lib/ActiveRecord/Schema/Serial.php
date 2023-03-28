<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Represents an auto-incrementing integer.
 *
 * - `SMALLSERIAL` is `Serial(Integer::SIZE_SMALL)`
 * - `SERIAL` is `Serial`
 * - `BIGSERIAL` is `Serial(Integer::SIZE_BIG)`
 *
 * For SQLite that's `INTEGER AUTOINCREMENT`
 *
 * @see https://www.postgresql.org/docs/current/datatype-numeric.html#DATATYPE-SERIAL
 * @see https://dev.mysql.com/doc/refman/8.0/en/numeric-type-syntax.html
 * @see https://www.sqlite.org/autoinc.html
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Serial extends Integer
{
    /**
     * @param array{ size: positive-int } $an_array
     */
    public static function __set_state(array $an_array): object
    {
        return new self($an_array['size']);
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
