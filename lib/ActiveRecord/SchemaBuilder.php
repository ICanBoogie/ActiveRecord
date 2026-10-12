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
use ICanBoogie\ActiveRecord\Schema\InvalidSchema;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\SchemaAttribute;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;
use ICanBoogie\ActiveRecord\Schema\Time;
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
     * @var list<non-empty-string>
     */
    private array $primary = [];

    /**
     * @var list<Index>
     */
    private array $indexes = [];

    /**
     * @param class-string<ActiveRecord>|null $record_class
     *     The initial schema is configured from the {@see ActiveRecord} class, when it is provided.
     *
     * @throws LogicException if the {@see $record_class} is not an {@see ActiveRecord} class.
     */
    public function __construct(
        public readonly ?string $record_class = null,
    ) {
        if ($record_class) {
            ActiveRecord\Config\Assert::extends_activerecord($record_class);

            $this->use_record($record_class);
        }
    }

    public function build(): Schema
    {
        $columns = $this->columns;

        count($columns) > 0
        or $this->error("The schema has no columns");

        $this->assert_primary();
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
     * Whether the builder is empty, i.e., no column is defined yet.
     */
    public function is_empty(): bool
    {
        return count($this->columns) === 0;
    }

    /**
     * Adds an index on one or multiple columns.
     *
     * @param non-empty-string|non-empty-list<non-empty-string> $columns
     *     Column names used by the index.
     * @param non-empty-string|null $name
     *     Name of the index.
     *
     * @return $this
     *
     * @throws InvalidSchema if an index with the same columns already exists.
     *
     * @see Index
     */
    public function add_index(
        array|string $columns,
        bool $unique = false,
        ?string $name = null
    ): self {
        $columns_ar = (array)$columns;

        foreach ($this->indexes as $i) {
            // The order of the columns matters
            if ((array)$i->columns === $columns_ar) {
                $columns_str = implode(', ', $columns_ar);

                $this->error("An index with the columns `$columns_str` already exists");
            }
        }

        $this->indexes[] = new Index($columns, $unique, $name);

        return $this;
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
        ?bool $default = null,
    ): self {
        return $this->add_column(
            $col_name,
            new Boolean(
                null: $null,
                default: $default,
            )
        );
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
        int|string|null $default = null,
        bool $primary = false,
    ): self {
        $this->add_column(
            $col_name,
            new Integer(
                size: $size,
                unsigned: $unsigned,
                serial: $serial,
                null: $null,
                unique: $unique,
                default: $default,
            )
        );

        if ($primary) {
            $this->add_primary($col_name);
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
        return $this->add_column(
            $col_name,
            new Decimal(
                precision: $precision,
                scale: $scale,
                approximate: $approximate,
                null: $null,
                unique: $unique
            )
        );
    }

    /**
     * Add a {@see Decimal} with a precision of 9 and approximate enabled.
     *
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
     * @phpstan-param Integer::SIZE_* $size
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
        $this->add_column(
            $col_name,
            new Serial(
                size: $size
            )
        );

        if ($primary) {
            $this->add_primary($col_name);
        }

        return $this;
    }

    /**
     * @param non-empty-string $col_name
     * @phpstan-param Integer::SIZE_* $size
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
        $this->add_column(
            $col_name,
            new Integer(
                size: $size,
                null: $null,
                unique: $unique,
            )
        );

        if ($primary) {
            $this->add_primary($col_name);
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
        return $this->add_column(
            $col_name,
            new Date(
                null: $null,
                default: $default,
            )
        );
    }

    /**
     * @param non-empty-string $col_name
     *
     * @return $this
     *
     * @see Time
     */
    public function add_time(
        string $col_name,
        bool $null = false,
        ?string $default = null,
    ): self {
        return $this->add_column(
            $col_name,
            new Time(
                null: $null,
                default: $default,
            )
        );
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
        return $this->add_column(
            $col_name,
            new DateTime(
                null: $null,
                default: $default,
                unique: $unique,
            )
        );
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
        return $this->add_column(
            $col_name,
            new Timestamp(
                null: $null,
                default: $default,
            )
        );
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
        $this->add_column(
            $col_name,
            new Character(
                size: $size,
                fixed: $fixed,
                null: $null,
                default: $default,
                unique: $unique,
                collate: $collate,
            )
        );

        if ($primary) {
            $this->add_primary($col_name);
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
        return $this->add_column(
            $col_name,
            new Binary(
                size: $size,
                fixed: $fixed,
                null: $null,
                unique: $unique,
            )
        );
    }

    /**
     * @param non-empty-string $col_name
     * @phpstan-param Blob::SIZE_* $size
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
        return $this->add_column(
            $col_name,
            new Blob(
                size: $size,
                null: $null,
                unique: $unique,
            )
        );
    }

    /**
     * @param non-empty-string $col_name
     * @phpstan-param Text::SIZE_* $size
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
        ?string $default = null,
        bool $unique = false,
        ?string $collate = null,
    ): self {
        return $this->add_column(
            $col_name,
            new Text(
                size: $size,
                null: $null,
                default: $default,
                unique: $unique,
                collate: $collate,
            )
        );
    }

    /**
     * @param non-empty-string $col_name
     * @param class-string<ActiveRecord> $associate
     *     The local key i.e. column name.
     * @phpstan-param Integer::SIZE_* $size
     * @param non-empty-string|null $as
     * @param OnDelete|null $on_delete
     *     If defined, a foreign key constraint is created, see {@see BelongsTo::$on_delete}.
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
        ?OnDelete $on_delete = null,
    ): self {
        return $this->add_column(
            $col_name,
            new BelongsTo(
                associate: $associate,
                size: $size,
                null: $null,
                unique: $unique,
                as: $as,
                on_delete: $on_delete,
            )
        );
    }

    /**
     * @throws InvalidSchema
     */
    private function error(string $message): never
    {
        throw new InvalidSchema($message, $this->record_class);
    }

    /**
     * Add a column to the schema.
     *
     * @param non-empty-string $name
     *
     * @return $this
     *
     * @throws InvalidSchema if the name is empty, or the column is already defined.
     */
    private function add_column(string $name, Column $column): self
    {
        // @phpstan-ignore-next-line // double checking, just in case
        $name !== ''
        or $this->error("A column name must not be empty");

        !isset($this->columns[$name])
        or $this->error("Column `$name` is already defined");

        $this->columns[$name] = $column;

        return $this;
    }

    /**
     * @param non-empty-string $primary
     */
    private function add_primary(string $primary): void
    {
        !in_array($primary, $this->primary, true)
        or $this->error("Column `$primary` is already used as primary key");

        $this->primary[] = $primary;
    }

    /**
     * Asserts that the columns used as primary key exist.
     */
    private function assert_primary(): void
    {
        foreach ($this->primary as $column) {
            isset($this->columns[$column])
            or $this->error("The column `$column`, used as primary key, is not defined");
        }
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
                isset($this->columns[$column])
                or $this->error("The column `$column`, used by an index, is not defined");
            }
        }
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     */
    private function use_record(string $activerecord_class): void
    {
        $class = new ReflectionClass($activerecord_class);

        foreach ($class->getAttributes(Index::class) as $attribute) {
            $index = $attribute->newInstance();

            $this->add_index(columns: $index->columns, unique: $index->unique, name: $index->name);
        }

        $ids = [];

        foreach ($class->getProperties() as $property) {
            // We only want the columns for this record, not its parent.
            if ($property->getDeclaringClass()->name !== $activerecord_class) {
                continue;
            }

            /** @var non-empty-string $name */
            $name = $property->name;

            foreach (
                $property->getAttributes(
                    SchemaAttribute::class,
                    ReflectionAttribute::IS_INSTANCEOF
                ) as $attribute
            ) {
                $attribute = $attribute->newInstance();

                if ($attribute instanceof Column) {
                    $this->add_column($name, $attribute);
                }

                if ($attribute instanceof Id) {
                    $ids[] = $name;
                }
            }
        }

        // Primary keys are added once the columns are defined.
        foreach ($ids as $name) {
            $this->add_primary($name);
        }
    }
}
