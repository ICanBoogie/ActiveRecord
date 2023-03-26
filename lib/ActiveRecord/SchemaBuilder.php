<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Attribute;
use ICanBoogie\ActiveRecord\Attribute\SchemaAttribute;
use LogicException;

use function array_filter;
use function in_array;
use function is_string;

final class SchemaBuilder
{
    public const SIZE_TINY = 'TINY';
    public const SIZE_SMALL = 'SMALL';
    public const SIZE_MEDIUM = 'MEDIUM';
    public const SIZE_BIG = 'BIG';

    public const NOW = 'NOW';
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /**
     * @var array<string, SchemaColumn>
     */
    private array $columns = [];

    /**
     * @var array<array{ string|array<string>, bool, ?string }>
     */
    private array $indexes = [];

    public function build(): Schema
    {
        $schema = new Schema($this->columns);

        foreach ($this->indexes as $index) {
            $schema->index(...$index);
        }

        return $schema;
    }

    /**
     * Whether the builder is empty i.e. no column is defined yet.
     */
    public function is_empty(): bool
    {
        return count($this->columns) === 0;
    }

    public function add_column(
        string $col_name,
        string $type,
        string|int|null $size = null,
        bool $unsigned = false,
        bool $null = false,
        mixed $default = null,
        bool $auto_increment = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        $this->columns[$col_name] = new SchemaColumn(
            type: $type,
            size: $size,
            unsigned: $unsigned,
            null: $null,
            default: $default,
            auto_increment: $auto_increment,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );

        return $this;
    }

    public function add_boolean(
        string $col_name,
        bool $null = false,
        /** @obsolete */
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = SchemaColumn::boolean(
            null: $null,
            unique: $unique,
        );

        return $this;
    }

    public function add_integer(
        string $col_name,
        int|string|null $size = null,
        bool $unsigned = false,
        bool $null = false,
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = SchemaColumn::int(
            size: $size,
            unsigned: $unsigned,
            null: $null,
            unique: $unique
        );

        return $this;
    }

    public function add_decimal(
        string $col_name,
        ?int $precision = null,
        bool $unsigned = false,
        bool $null = false,
    ): self {
        $this->columns[$col_name] = SchemaColumn::float(
            precision: $precision,
            unsigned: $unsigned,
            null: $null
        );

        return $this;
    }

    public function add_serial(
        string $col_name,
        bool $primary = false,
    ): self {
        $this->columns[$col_name] = SchemaColumn::serial(
            primary: $primary,
        );

        return $this;
    }

    public function add_foreign(
        string $col_name,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
    ): self {
        $this->columns[$col_name] = SchemaColumn::foreign(
            null: $null,
            unique: $unique,
            primary: $primary,
        );

        return $this;
    }

    public function add_date(
        string $col_name,
        bool $null = false,
        ?string $default = null,
    ): self {
        $this->columns[$col_name] = SchemaColumn::date(
            null: $null,
            default: $default,
        );

        return $this;
    }

    public function add_datetime(
        string $col_name,
        bool $null = false,
        ?string $default = null,
    ): self {
        $this->columns[$col_name] = SchemaColumn::datetime(
            null: $null,
            default: $default,
        );

        return $this;
    }

    public function add_timestamp(
        string $col_name,
        bool $null = false,
        ?string $default = null,
    ): self {
        $this->columns[$col_name] = SchemaColumn::timestamp(
            null: $null,
            default: $default,
        );

        return $this;
    }

    public function add_char(
        string $col_name,
        int $size = 255,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        $this->columns[$col_name] = SchemaColumn::char(
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );

        return $this;
    }

    public function add_varchar(
        string $col_name,
        int $size = 255,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        $this->columns[$col_name] = SchemaColumn::varchar(
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );

        return $this;
    }

    public function add_binary(
        string $col_name,
        int $size = 255,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
    ): self {
        $this->columns[$col_name] = new SchemaColumn(
            type: SchemaColumn::TYPE_BINARY,
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
        );

        return $this;
    }

    public function add_varbinary(
        string $col_name,
        int $size = 255,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
    ): self {
        $this->columns[$col_name] = new SchemaColumn(
            type: SchemaColumn::TYPE_VARBINARY,
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
        );

        return $this;
    }

    public function add_blob(
        string $col_name,
        string|null $size = null,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        $this->columns[$col_name] = SchemaColumn::blob(
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );

        return $this;
    }

    public function add_text(
        string $col_name,
        string|null $size = null,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
        ?string $comment = null,
        ?string $collate = null,
    ): self {
        $this->columns[$col_name] = SchemaColumn::text(
            size: $size,
            null: $null,
            unique: $unique,
            primary: $primary,
            comment: $comment,
            collate: $collate,
        );

        return $this;
    }

    /**
     * Adds an index on one or multiple columns.
     *
     * @param string|array<string> $columns
     *     Identifiers of the columns making the unique index.
     *
     * @return $this
     *
     * @throws LogicException if a column used by the index is not defined.
     */
    public function add_index(
        array|string $columns,
        bool $unique = false,
        ?string $name = null
    ): self {
        if (is_string($columns)) {
            $columns = [ $columns ];
        }

        foreach ($columns as $column) {
            $this->columns[$column] ??
                throw new LogicException("Column used by index is not defined: $column");
        }

        $this->indexes[] = [ $columns, $unique, $name ];

        return $this;
    }

    /**
     * @param SchemaAttribute[] $class_attributes
     * @param array{ SchemaAttribute, non-empty-string }[] $property_attributes
     *
     * @return $this
     */
    public function from_attributes(
        array $class_attributes,
        array $property_attributes,
    ): self {
        $ids = [];

        $property_attributes = array_filter($property_attributes, function ($ar) use (&$ids) {
            [ $attribute, $property ] = $ar;

            if ($attribute instanceof Attribute\Id) {
                $ids[] = $property;

                return false;
            }

            return true;
        });

        foreach ($property_attributes as [ $attribute, $name ]) {
            $is_primary = in_array($name, $ids);

            match ($attribute::class) {
                // Numeric Data Types

                Attribute\Boolean::class => $this->add_boolean(
                    col_name: $name,
                    null: $attribute->null,
                ),

                Attribute\Integer::class => $this->add_integer(
                    col_name: $name,
                    size: $attribute->size,
                    unsigned: $attribute->unsigned,
                    null: $attribute->null,
                    unique: $attribute->unique,
                ),

                Attribute\Serial::class => $this->add_serial(
                    col_name: $name,
                    primary: in_array($name, $ids)
                ),

                // Date and Time Data Types

                Attribute\Date::class => $this->add_date(
                    col_name: $name,
                    null: $attribute->null,
                    default: $attribute->default,
                ),

                Attribute\DateTime::class => $this->add_datetime(
                    col_name: $name,
                    null: $attribute->null,
                    default: $attribute->default,
                ),

                // String Data Types

                Attribute\Char::class => $this->add_char(
                    col_name: $name,
                    size: $attribute->size,
                    null: $attribute->null,
                    unique: $attribute->unique,
                    primary: $is_primary,
                    collate: $attribute->collate,
                ),

                Attribute\VarChar::class => $this->add_varchar(
                    col_name: $name,
                    size: $attribute->size,
                    null: $attribute->null,
                    unique: $attribute->unique,
                    primary: $is_primary,
                    collate: $attribute->collate,
                ),

                Attribute\Binary::class => $this->add_binary(
                    col_name: $name,
                    size: $attribute->size,
                    null: $attribute->null,
                    unique: $attribute->unique,
                    primary: $is_primary,
                ),

                Attribute\VarBinary::class => $this->add_varbinary(
                    col_name: $name,
                    size: $attribute->size,
                    null: $attribute->null,
                    unique: $attribute->unique,
                    primary: $is_primary,
                ),

                Attribute\Text::class => $this->add_text(
                    col_name: $name,
                    size: $attribute->size,
                    null: $attribute->null,
                    unique: $attribute->unique,
                    primary: $is_primary,
                ),

                // Relations

                Attribute\BelongsTo::class => $this->add_foreign(
                    col_name: $name,
                    null: $attribute->null,
                    unique: $attribute->unique,
                    primary: $is_primary,
                ),

                default => throw new LogicException("Don't know what to do with " . $attribute::class)
            };
        }

        foreach ($class_attributes as $attribute) {
            match ($attribute::class) {
                Attribute\Index::class => $this->add_index(
                    columns: $attribute->columns,
                    unique: $attribute->unique,
                    name: $attribute->name,
                ),

                Attribute\HasMany::class => null,

                default => throw new LogicException("Don't know what to do with " . $attribute::class)
            };
        }

        return $this;
    }
}
