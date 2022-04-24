<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\Accessor\AccessorTrait;
use LogicException;

use function array_filter;
use function implode;
use function in_array;
use function is_numeric;
use function strtoupper;

/**
 * Representation of a schema column.
 *
 * @property-read bool $is_serial
 * @property-read string $formatted_type_attributes
 * @property-read string $formatted_auto_increment
 * @property-read string $formatted_collate
 * @property-read string $formatted_comment
 * @property-read string $formatted_default
 * @property-read string $formatted_index
 * @property-read string $formatted_null
 * @property-read string $formatted_type
 */
class SchemaColumn
{
    /**
     * @uses get_is_serial
     * @uses get_formatted_type_attributes
     * @uses get_formatted_auto_increment
     * @uses get_formatted_collate
     * @uses get_formatted_comment
     * @uses get_formatted_default
     * @uses get_formatted_key
     * @uses get_formatted_null
     * @uses get_formatted_type
     */
    use AccessorTrait;

    public static function __set_state(array $an_array): self // @phpstan-ignore-line
    {
        return new self(
            $an_array['type'],
            $an_array['size'],
            $an_array['unsigned'],
            $an_array['null'],
            $an_array['default'],
            $an_array['auto_increment'],
            $an_array['unique'],
            $an_array['primary'],
            $an_array['comment'],
            $an_array['collate'],
        );
    }

    public const TYPE_BLOB = 'BLOB';
    public const TYPE_BOOLEAN = 'BOOLEAN';
    public const TYPE_CHAR = 'CHAR';
    public const TYPE_DATETIME = 'DATETIME';
    public const TYPE_FLOAT = 'FLOAT';
    public const TYPE_INT = 'INT';
    public const TYPE_TIMESTAMP = 'TIMESTAMP';
    public const TYPE_TEXT = 'TEXT';
    public const TYPE_VARCHAR = 'VARCHAR';

    public const SIZE_TINY = 'TINY';
    public const SIZE_SMALL = 'SMALL';
    public const SIZE_MEDIUM = 'MEDIUM';
    public const SIZE_BIG = 'BIG';

    public const NOW = 'NOW';
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /*
     * https://dev.mysql.com/doc/refman/8.0/en/numeric-types.html
     */

    public static function boolean(
        bool $null = false,
        bool $unique = false,
    ): self {
        return new self(
            type: self::TYPE_INT,
            size: 1,
            null: $null,
            unique: $unique,
        );
    }

    public static function int(
        int|string|null $size = null,
        bool $unsigned = false,
        bool $null = false,
        bool $unique = false,
    ): self {
        return new self(
            type: self::TYPE_INT,
            size: $size,
            unsigned: $unsigned,
            null: $null,
            unique: $unique,
        );
    }

    public static function float(
        ?int $precision = null,
        bool $unsigned = false,
        bool $null = false,
    ): self {
        return new self(
            type: self::TYPE_FLOAT,
            size: $precision,
            unsigned: $unsigned,
            null: $null,
        );
    }

    /**
     * @see https://dev.mysql.com/doc/refman/8.0/en/numeric-type-syntax.html
     */
    public static function serial(
        bool $primary = false
    ): self {
        return new self(
            type: self::TYPE_INT,
            size: self::SIZE_BIG,
            unsigned: true,
            null: false,
            auto_increment: true,
            unique: !$primary,
            primary: $primary,
        );
    }

    public static function foreign(
        bool $unique = false,
        bool $primary = false,
    ): self {
        return new self(
            type: self::TYPE_INT,
            size: self::SIZE_BIG,
            unsigned: true,
            null: false,
            unique: $primary ? false : $unique,
            primary: $primary,
        );
    }

    /*
     * https://dev.mysql.com/doc/refman/8.0/en/datetime.html
     */

    public static function datetime(
        bool $null = false,
        ?string $default = null,
    ): self {
        self::assert_datetime_default($default);

        return new self(
            type: self::TYPE_DATETIME,
            null: $null,
            default: $default,
        );
    }

    public static function timestamp(
        bool $null = false,
        ?string $default = null,
    ): self {
        self::assert_datetime_default($default);

        return new self(
            type: self::TYPE_TIMESTAMP,
            null: $null,
            default: $default,
        );
    }

    private static function assert_datetime_default(?string $default): void
    {
        if ($default && !in_array($default, [ self::NOW, self::CURRENT_TIMESTAMP ])) {
            throw new LogicException("Can only be one of 'NOW' or 'CURRENT_TIMESTAMP', given: $default.");
        }
    }

    /*
     * https://dev.mysql.com/doc/refman/8.0/en/string-types.html
     */

    public static function char(
        int $size = 255,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        return new self(
            type: self::TYPE_CHAR,
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );
    }

    public static function varchar(
        int $size = 255,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        return new self(
            type: self::TYPE_VARCHAR,
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );
    }

    public static function blob(
        string|null $size = null,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        $size = match ($size) {
            self::SIZE_SMALL => null,
            self::SIZE_BIG => 'LONG',
            default => $size,
        };

        return new self(
            type: self::TYPE_BLOB,
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );
    }

    public static function text(
        string|null $size = null,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        $size = match ($size) {
            self::SIZE_SMALL => null,
            self::SIZE_BIG => 'LONG',
            default => $size,
        };

        return new self(
            type: self::TYPE_TEXT,
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );
    }

    public function __construct(
        public string $type,
        public string|int|null $size = null,
        public bool $unsigned = false,
        public bool $null = false,
        public mixed $default = null,
        public bool $auto_increment = false,
        public bool $unique = false,
        public bool $primary = false,
        public ?string $comment = null,
        public ?string $collate = null,
    ) {
        $this->type = strtoupper($type);
    }

    /**
     * Returns the formatted type, including the size.
     */
    protected function get_formatted_type(): string
    {
        $type = strtoupper($this->type);
        $size = $this->size;

        if (is_numeric($size)) {
            if ($type === self::TYPE_INT) {
                return match ($size) {
                    1 => self::SIZE_TINY . self::TYPE_INT,
                    2 => self::SIZE_SMALL . self::TYPE_INT,
                    3 => self::SIZE_MEDIUM . self::TYPE_INT,
                    4 => self::TYPE_INT,
                    8 => self::SIZE_BIG . self::TYPE_INT,
                    default => "$type($size)",
                };
            }

            return "$type($size)";
        }

        if ($size) {
            return strtoupper($size) . $type;
        }

        return $type;
    }

    /**
     * Returns the formatted default.
     */
    protected function get_formatted_default(): string
    {
        $default = $this->default;

        return match ($default) {
            null => '',
            self::CURRENT_TIMESTAMP => "DEFAULT CURRENT_TIMESTAMP",
            self::NOW => "DEFAULT NOW",
            default => "DEFAULT '$default'",
        };
    }

    /**
     * Returns the formatted attributes.
     */
    protected function get_formatted_type_attributes(): string
    {
        return $this->unsigned ? 'UNSIGNED' : '';
    }

    /**
     * Returns the formatted null.
     */
    protected function get_formatted_null(): string
    {
        return $this->null ? 'NULL' : 'NOT NULL';
    }

    /**
     * Returns the formatted index.
     */
    protected function get_formatted_key(): string
    {
        return implode(' ', array_filter([

            $this->unique ? 'UNIQUE' : '',
            $this->primary ? 'PRIMARY KEY' : '',

        ]));
    }

    /**
     * Returns the formatted comment.
     */
    protected function get_formatted_comment(): string
    {
        return $this->comment ? "`$this->comment`" : '';
    }

    /**
     * Returns the formatted charset.
     */
    protected function get_formatted_collate(): string
    {
        return $this->collate ? "COLLATE $this->collate" : '';
    }

    /**
     * Returns the formatted auto increment.
     */
    protected function get_formatted_auto_increment(): string
    {
        return $this->auto_increment ? 'AUTO_INCREMENT' : '';
    }

    /**
     * Whether the column is a serial column.
     */
    protected function get_is_serial(): bool
    {
        return $this->type == self::TYPE_INT
            && !$this->null
            && $this->auto_increment
            && ($this->primary || $this->unique);
    }

    /**
     * Renders the column into a string.
     */
    public function render(): string
    {
        return implode(' ', array_filter([
            $this->get_formatted_type(),
            $this->get_formatted_type_attributes(),
            $this->get_formatted_null(),
            $this->get_formatted_default(),
            $this->get_formatted_auto_increment(),
            $this->get_formatted_key(),
            $this->get_formatted_comment(),
            $this->get_formatted_collate(),
        ]));
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
