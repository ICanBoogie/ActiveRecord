<?php

namespace ICanBoogie\ActiveRecord\Schema;

/**
 * Base for columns.
 */
abstract class Column implements SchemaAttribute
{
    /**
     * @param bool $null
     *     Whether values can be nullable.
     *     Values are not nullable by default.
     * @param bool $unique
     *     Whether values must be unique.
     *     Values are not unique by default.
     * @param non-empty-string|null $collate
     *     A collation identifier.
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/adding-collation.html
     */
    public function __construct(
        public readonly bool $null = false,
        public readonly ?string $default = null,
        public readonly bool $unique = false,
        public readonly ?string $collate = null,
    ) {
    }
}
