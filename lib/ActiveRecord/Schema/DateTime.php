<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use LogicException;

use function in_array;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateTime extends Constraints implements ColumnAttribute
{
    public const NOW = 'NOW';
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    public static function assert_datetime_default(?string $default): void
    {
        if ($default && !in_array($default, [ self::NOW, self::CURRENT_TIMESTAMP ])) {
            throw new LogicException("Can only be one of 'NOW' or 'CURRENT_TIMESTAMP', given: $default.");
        }
    }
    /**
     * @param array{
     *     null: bool,
     *     default: ?string,
     *     unique: bool,
     * } $an_array
     */
    public static function __set_state(array $an_array): object
    {
        return new self(
            $an_array['null'],
            $an_array['default'],
            $an_array['unique'],
        );
    }

    public function __construct(
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
    ) {
        self::assert_datetime_default($default);

        parent::__construct(
            null: $null,
            default: $default,
            unique: $unique,
        );
    }
}
