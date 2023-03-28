<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

/**
 * Represents a decimal value:
 *
 * https://dev.mysql.com/doc/refman/8.0/en/fixed-point-types.html
 * https://dev.mysql.com/doc/refman/8.0/en/floating-point-types.html
 * https://www.postgresql.org/docs/current/datatype-numeric.html#DATATYPE-NUMERIC-DECIMAL
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Decimal extends Constraints implements SchemaColumn
{
    /**
     * @param positive-int $precision
     *     The total count of significant digits in the whole number, that is,
     *     the number of digits to both sides of the decimal point.
     * @param int $scale
     *     The count of decimal digits in the fractional part, to the right of the decimal point.
     *     So the number 23.5141 has a precision of 6 and a scale of 4.
     * @param bool $approximate
     *     Whether stored values can be approximate i.e. inexact.
     *     If `true` resulting column type will be `FLOAT` or `DOUBLE` instead of `NUMERIC`, `DECIMAL`, or `REAL`.
     */
    public function __construct(
        public readonly int $precision,
        public readonly int $scale = 0,
        public readonly bool $approximate = false,
        bool $null = false,
        bool $unique = false,
    ) {
        parent::__construct(
            null: $null,
            unique: $unique,
        );
    }
}
