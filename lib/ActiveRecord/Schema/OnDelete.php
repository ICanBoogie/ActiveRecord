<?php

namespace ICanBoogie\ActiveRecord\Schema;

/**
 * What happens to a row when the row it references is deleted.
 *
 * @see BelongsTo::$on_delete
 */
enum OnDelete: string
{
    /**
     * The row is deleted as well.
     */
    case Cascade = 'CASCADE';

    /**
     * The reference is set to `NULL`. The column must be nullable.
     */
    case SetNull = 'SET NULL';

    /**
     * The deletion fails, immediately.
     */
    case Restrict = 'RESTRICT';

    /**
     * The deletion fails, when the constraint is checked.
     */
    case NoAction = 'NO ACTION';
}
