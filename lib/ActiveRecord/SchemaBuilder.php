<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\Blob;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\ColumnAttribute;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Decimal;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\SchemaAttribute;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema\Timestamp;
use ICanBoogie\ActiveRecord\Schema\VarBinary;
use LogicException;

use function is_string;

final class SchemaBuilder
{
    public const SIZE_TINY = 'TINY';

    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    /**
     * @var array<non-empty-string, ColumnAttribute>
     */
    private array $columns = [];

    /**
     * @var array<non-empty-string>
     */
    private array $primary = [];

    /**
     * @var array<Index>
     */
    private array $indexes = [];

    public function build(): Schema
    {
        assert(count($this->columns) > 0);

        $primary = match (count($this->primary)) {
            0 => null,
            1 => reset($this->primary),
            default => $this->primary,
        };

        return new Schema(
            columns: $this->columns,
            primary: $primary,
            indexes: $this->indexes
        );
    }

    /**
     * Whether the builder is empty i.e. no column is defined yet.
     */
    public function is_empty(): bool
    {
        return count($this->columns) === 0;
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see Boolean
     */
    public function add_boolean(
        string $col_name,
        bool $null = false,
    ): self {
        $this->columns[$col_name] = new Boolean(
            null: $null,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     * @param positive-int $size
     *
     * @return $this
     *
     * @see Integer
     */
    public function add_integer(
        string $col_name,
        int $size = Integer::SIZE_REGULAR,
        bool $unsigned = false,
        bool $serial = false,
        bool $null = false,
        bool $unique = false,
        int|string $default = null,
        bool $primary = false,
    ): self {
        $this->columns[$col_name] = new Integer(
            size: $size,
            unsigned: $unsigned,
            serial: $serial,
            null: $null,
            unique: $unique,
            default: $default,
        );

        if ($primary) {
            $this->primary[] = $col_name;
        }

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     * @param positive-int $precision
     *
     * @return $this
     *
     * @see Decimal
     */
    public function add_decimal(
        string $col_name,
        int $precision,
        int $scale = 0,
        bool $approximate = false,
        bool $null = false,
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = new Decimal(
            precision: $precision,
            scale: $scale,
            approximate: $approximate,
            null: $null,
            unique: $unique
        );

        return $this;
    }

    public function add_float(
        string $col_name,
        bool $null = false,
        bool $unique = false,
    ): self {
        return $this->add_decimal(
            col_name: $col_name,
            precision: 9,
            approximate: true,
            null: $null,
            unique: $unique,
        );
    }

    /**
     * @param non-empty-string $col_name
     * @param positive-int $size
     *
     * @return $this
     *
     * @see Serial
     */
    public function add_serial(
        string $col_name,
        int $size = Integer::SIZE_REGULAR,
        bool $primary = false,
    ): self {
        $this->columns[$col_name] = new Serial(
            size: $size
        );

        if ($primary) {
            $this->primary[] = $col_name;
        }

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     * @param positive-int $size
     *
     * @return $this
     *
     * @see Integer
     */
    public function add_foreign(
        string $col_name,
        int $size = Integer::SIZE_REGULAR,
        bool $null = false,
        bool $unique = false,
        bool $primary = false,
    ): self {
        $this->columns[$col_name] = new Integer(
            size: $size,
            null: $null,
            unique: $unique,
        );

        if ($primary) {
            $this->primary[] = $col_name;
        }

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see Date
     */
    public function add_date(
        string $col_name,
        bool $null = false,
        ?string $default = null,
    ): self {
        $this->columns[$col_name] = new Date(
            null: $null,
            default: $default,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see DateTime
     */
    public function add_datetime(
        string $col_name,
        bool $null = false,
        ?string $default = null,
    ): self {
        $this->columns[$col_name] = new DateTime(
            null: $null,
            default: $default,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see Timestamp
     */
    public function add_timestamp(
        string $col_name,
        bool $null = false,
        ?string $default = null,
    ): self {
        $this->columns[$col_name] = new Timestamp(
            null: $null,
            default: $default,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     * @param positive-int $size
     * @param non-empty-string|null $collate
     *
     * @return $this
     *
     * @see Character
     */
    public function add_character(
        string $col_name,
        int $size = 255,
        bool $fixed = false,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
        ?string $collate = null,
        bool $primary = false,
    ): self {
        $this->columns[$col_name] = new Character(
            size: $size,
            fixed: $fixed,
            null: $null,
            default: $default,
            unique: $unique,
            collate: $collate,
        );

        if ($primary) {
            $this->primary[] = $col_name;
        }

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see Binary
     */
    public function add_binary(
        string $col_name,
        int $size = 255,
        bool $null = false,
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = new Binary(
            size: $size,
            null: $null,
            unique: $unique,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see VarBinary
     */
    public function add_varbinary(
        string $col_name,
        int $size = 255,
        bool $null = false,
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = new VarBinary(
            size: $size,
            null: $null,
            unique: $unique,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see Blob
     */
    public function add_blob(
        string $col_name,
        int $size = 255,
        bool $null = false,
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = new Blob(
            size: $size,
            null: $null,
            unique: $unique,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     * @param non-empty-string|null $collate
     *
     * @return $this
     *
     * @see Text
     */
    public function add_text(
        string $col_name,
        string $size = Text::SIZE_REGULAR,
        bool $null = false,
        string $default = null,
        bool $unique = false,
        string $collate = null,
    ): self {
        $this->columns[$col_name] = new Text(
            size: $size,
            null: $null,
            default: $default,
            unique: $unique,
            collate: $collate,
        );

        return $this;
    }

    /**
     * Adds an index on one or multiple columns.
     *
     * @param non-empty-string|non-empty-array<non-empty-string> $columns
     *     Identifiers of the columns making the unique index.
     *
     * @return $this
     *
     * @throws LogicException if a column used by the index is not defined.
     *
     * @see Index
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

        $this->indexes[] = new Index($columns, $unique, $name);

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
        foreach ($property_attributes as [ $attribute, $name ]) {
            if ($attribute instanceof Schema\Id) {
                $this->primary[] = $name;

                continue;
            }

            if ($attribute instanceof ColumnAttribute) {
                $this->columns[$name] = $attribute;
            }
        }

        foreach ($class_attributes as $attribute) {
            if ($attribute instanceof Schema\Index) {
                $this->indexes[] = $attribute;
            }
        }

        return $this;
    }
}
