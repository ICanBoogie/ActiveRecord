<?php

namespace ICanBoogie\ActiveRecord;

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

    public function add_boolean(
        string $col_name,
        bool $null = false,
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
     */
    public function add_index(
        array|string $columns,
        bool $unique = false,
        ?string $name = null
    ): self {
        $this->indexes[] = [ $columns, $unique, $name ];

        return $this;
    }
}
