<?php

namespace ICanBoogie\ActiveRecord\Schema;

/**
 * A foreign key constraint.
 *
 * Foreign keys are resolved by {@see \ICanBoogie\ActiveRecord\ConfigBuilder} from the
 * {@see BelongsTo} columns that define {@see BelongsTo::$on_delete}.
 */
final readonly class ForeignKey
{
    /**
     * @param array{
     *     column: non-empty-string,
     *     table: non-empty-string,
     *     references: non-empty-string,
     *     on_delete: OnDelete,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param non-empty-string $column
     *     The local column.
     * @param non-empty-string $table
     *     The referenced table, without the prefix defined by the connection.
     * @param non-empty-string $references
     *     The referenced column.
     */
    public function __construct(
        public string $column,
        public string $table,
        public string $references,
        public OnDelete $on_delete,
    ) {
    }
}
