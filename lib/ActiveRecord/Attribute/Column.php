<?php

namespace ICanBoogie\ActiveRecord\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column implements SchemaAttribute
{
    /*
     * Numeric Data Types
     *
     * https://dev.mysql.com/doc/refman/8.0/en/numeric-types.html
     */

    // https://dev.mysql.com/doc/refman/8.0/en/integer-types.html
    public const TYPE_INTEGER = 'INTEGER';

    // https://dev.mysql.com/doc/refman/8.0/en/fixed-point-types.html
    public const TYPE_DECIMAL = 'DECIMAL';

    /*
     * Date and Time Data Types
     *
     * https://dev.mysql.com/doc/refman/8.0/en/date-and-time-types.html
     */

    // https://dev.mysql.com/doc/refman/8.0/en/datetime.html
    public const TYPE_DATE = 'DATE';

    // https://dev.mysql.com/doc/refman/8.0/en/datetime.html
    public const TYPE_DATETIME = 'DATETIME';

    // https://dev.mysql.com/doc/refman/8.0/en/datetime.html
    public const TYPE_TIMESTAMP = 'TIMESTAMP';

    // @see https://dev.mysql.com/doc/refman/8.0/en/time.html
    public const TYPE_TIME = 'TIME';

    /*
     * String Data Types
     *
     * https://dev.mysql.com/doc/refman/8.0/en/string-types.html
     */

    // https://dev.mysql.com/doc/refman/8.0/en/char.html
    public const TYPE_CHAR = 'CHAR';

    // https://dev.mysql.com/doc/refman/8.0/en/char.html
    public const TYPE_VARCHAR = 'VARCHAR';

    // https://dev.mysql.com/doc/refman/8.0/en/binary-varbinary.html
    public const TYPE_BINARY = 'BINARY';

    // https://dev.mysql.com/doc/refman/8.0/en/binary-varbinary.html
    public const TYPE_VARBINARY = 'VARBINARY';

    // https://dev.mysql.com/doc/refman/8.0/en/blob.html
    public const TYPE_BLOB = 'BLOB';

    // https://dev.mysql.com/doc/refman/8.0/en/blob.html
    public const TYPE_TEXT = 'TEXT';

    /*
     * Data type sizes
     *
     * e.g. for INTEGER: 1, 2, 3, 4, 8.
     */
    public const SIZE_TINY = 'TINY';
    public const SIZE_SMALL = 'SMALL';
    public const SIZE_MEDIUM = 'MEDIUM';
    public const SIZE_REGULAR = 'REGULAR';
    public const SIZE_BIG = 'BIG';

    /*
     * Default values for time
     */
    public const NOW = 'NOW';
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    public function __construct(
        public readonly string $type,
        public readonly string|int|null $size = null,
        public readonly bool $unsigned = false,
        public readonly bool $null = false,
        public readonly mixed $default = null,
        public readonly bool $auto_increment = false,
        public readonly bool $unique = false,
        public readonly ?string $collate = null,
    ) {
    }
}
