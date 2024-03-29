<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Binary;
use ICanBoogie\ActiveRecord\Schema\Blob;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Decimal;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\SchemaAttribute;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema\Timestamp;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;

use function is_string;

final class SchemaBuilder
{
    /**
     * @var array<non-empty-string, Column>
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
        $columns = $this->columns;
        assert(count($columns) > 0);

        $this->assert_indexes();

        $primary = match (count($this->primary)) {
            0 => null,
            1 => reset($this->primary),
            default => $this->primary,
        };

        return new Schema(
            columns: $columns,
            primary: $primary,
            indexes: $this->indexes
        );
    }

    /**
     * Asserts that the columns used by the indexes exist.
     */
    private function assert_indexes(): void
    {
        foreach ($this->indexes as $index) {
            $columns = $index->columns;

            if (is_string($columns)) {
                $columns = [ $columns ];
            }

            foreach ($columns as $column) {
                $this->columns[$column]
                    ?? throw new LogicException("Column used by index is not defined: $column");
            }
        }
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
     *
     * @phpstan-param Integer::SIZE_* $size
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

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see add_decimal
     */
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
     * @param non-empty-string|null $default
     *
     * @return $this
     *
     * @see DateTime
     */
    public function add_datetime(
        string $col_name,
        bool $null = false,
        ?string $default = null,
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = new DateTime(
            null: $null,
            default: $default,
            unique: $unique,
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
     * @param positive-int $size
     *
     * @return $this
     *
     * @see Binary
     */
    public function add_binary(
        string $col_name,
        int $size = 255,
        bool $fixed = false,
        bool $null = false,
        bool $unique = false,
    ): self {
        $this->columns[$col_name] = new Binary(
            size: $size,
            fixed: $fixed,
            null: $null,
            unique: $unique,
        );

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     * @param Blob::SIZE_* $size
     *
     * @return $this
     *
     * @see Blob
     */
    public function add_blob(
        string $col_name,
        string $size = Blob::SIZE_REGULAR,
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
     * @param Text::SIZE_* $size
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
     * @param non-empty-string $col_name
     * @param class-string<ActiveRecord> $associate
     *     The local key i.e. column name.
     * @param Integer::SIZE_* $size
     * @param non-empty-string|null $as
     *
     * @return $this
     *
     * @see BelongsTo
     */
    public function belongs_to(
        string $col_name,
        string $associate,
        int $size = Integer::SIZE_REGULAR,
        bool $null = false,
        bool $unique = false,
        ?string $as = null,
    ): self {
        $this->columns[$col_name] = new BelongsTo(
            associate: $associate,
            size: $size,
            null: $null,
            unique: $unique,
            as: $as,
        );

        return $this;
    }

    /**
     * Adds an index on one or multiple columns.
     *
     * @param non-empty-string|non-empty-array<non-empty-string> $columns
     *     Identifiers of the columns making the unique index.
     * @param non-empty-string|null $name
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
        $this->indexes[] = new Index($columns, $unique, $name);

        return $this;
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     *
     * @return $this
     * @internal
     *
     */
    public function use_record(string $activerecord_class): self
    {
        $class = new ReflectionClass($activerecord_class);

        foreach ($class->getAttributes(Index::class) as $attribute) {
            $this->indexes[] = $attribute->newInstance();
        }

        foreach ($class->getProperties() as $property) {
            // We only want the columns for this record, not its parent.
            if ($property->getDeclaringClass()->name !== $activerecord_class) {
                continue;
            }

            foreach (
                $property->getAttributes(
                    SchemaAttribute::class,
                    ReflectionAttribute::IS_INSTANCEOF
                ) as $attribute
            ) {
                $attribute = $attribute->newInstance();

                if ($attribute instanceof Id) {
                    $this->primary[] = $property->name;

                    continue;
                }

                if ($attribute instanceof Column) {
                    $this->columns[$property->name] = $attribute;
                }
            }
        }

        return $this;
    }
}
