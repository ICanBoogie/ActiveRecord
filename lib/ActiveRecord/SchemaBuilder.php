<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema\ColumnAttribute;
use ICanBoogie\ActiveRecord\Schema\SchemaAttribute;
use LogicException;

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

    private function add_column_from_attribute(ColumnAttribute $attribute, string $col_name, bool $is_primary): void
    {
        match ($attribute::class) {
            //
            // Integer
            //
            Schema\Boolean::class,
            Schema\Integer::class,
            Schema\Serial::class => $this->add_column(
                col_name: $col_name,
                type: SchemaColumn::TYPE_INT,
                size: $attribute->size,
                unsigned: $attribute->unsigned,
                null: $attribute->null,
                auto_increment: $attribute->serial,
                unique: $attribute->unique,
                primary: $is_primary
            ),

            //
            // Decimal @TODO: IMPROVE SUPPORT
            //
            Schema\Decimal::class => $this->add_decimal(
                col_name: $col_name,
                null: $attribute->null,
            ),

            Schema\Date::class => $this->add_date(
                col_name: $col_name,
                null: $attribute->null,
                default: $attribute->default,
            ),

            Schema\DateTime::class => $this->add_datetime(
                col_name: $col_name,
                null: $attribute->null,
                default: $attribute->default,
            ),

            // String Data Types

            Schema\Character::class => $this->add_character(
                col_name: $col_name,
                size: $attribute->size,
                fixed: $attribute->fixed,
                null: $attribute->null,
                unique: $attribute->unique,
                collate: $attribute->collate,
                primary: $is_primary,
            ),

            Schema\Binary::class => $this->add_binary(
                col_name: $col_name,
                size: $attribute->size,
                null: $attribute->null,
                unique: $attribute->unique,
                primary: $is_primary,
            ),

            Schema\VarBinary::class => $this->add_varbinary(
                col_name: $col_name,
                size: $attribute->size,
                null: $attribute->null,
                unique: $attribute->unique,
                primary: $is_primary,
            ),

            Schema\Text::class => $this->add_text(
                col_name: $col_name,
                size: $attribute->size,
                null: $attribute->null,
                unique: $attribute->unique,
                primary: $is_primary,
            ),

            // Relations

            Schema\BelongsTo::class => $this->add_foreign(
                col_name: $col_name,
                null: $attribute->null,
                unique: $attribute->unique,
                primary: $is_primary,
            ),

            default => throw new LogicException("Don't know what to do with: " . $attribute::class),
        };
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

    public function add_character(
        string $col_name,
        int $size = 255,
        bool $fixed = false,
        bool $null = false,
        bool $unique = false,
        ?string $collate = null,
        bool $primary = false,
        ?string $comment = null,
    ): self {
        $this->columns[$col_name] = new SchemaColumn(
            type: $fixed ? SchemaColumn::TYPE_CHAR : SchemaColumn::TYPE_VARCHAR,
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

        //
        // Before we process the property attributes, we need look for Id markers,
        // so we can property set the `primary` property.
        //
        foreach ($property_attributes as [ $attribute, $property ]) {
            if ($attribute instanceof Schema\Id) {
                $ids[] = $property;
            }
        }

        foreach ($property_attributes as [ $attribute, $name ]) {
            if (!$attribute instanceof ColumnAttribute) {
                continue;
            }

            $is_primary = in_array($name, $ids);
            $this->add_column_from_attribute($attribute, $name, $is_primary);
        }

        foreach ($class_attributes as $attribute) {
            if ($attribute instanceof Schema\Index) {
                $this->add_index(
                    columns: $attribute->columns,
                    unique: $attribute->unique,
                    name: $attribute->name,
                );
            }
        }

        return $this;
    }
}
