<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Text extends Constraints implements SchemaColumn
{
    public const SIZE_SMALL = 'SMALL';
    public const SIZE_MEDIUM = 'MEDIUM';
    public const SIZE_REGULAR = '';
    public const SIZE_LONG = 'LONG';

    /**
     * @param string $size
     *     One of {@link SIZE_SMALL}, {@link SIZE_MEDIUM}, {@link SIZE_REGULAR}, {@link SIZE_LONG}.
     */
    public function __construct(
        public readonly string $size = self::SIZE_REGULAR,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
        ?string $collate = null,
    ) {
        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
            collate: $collate,
        );
    }
}
