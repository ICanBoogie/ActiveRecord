<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\SchemaAttribute;
use ReflectionClass;

use function assert;
use function ICanBoogie\trim_suffix;
use function printf;
use function strlen;

final class AssociationBuilder
{
    /**
     * @var array<TransientBelongsToAssociation>
     */
    private array $belongs_to = [];

    /**
     * @var array<TransientHasManyAssociation>
     */
    private array $has_many = [];

    public function build(): TransientAssociation
    {
        return new TransientAssociation(
            has_many:  $this->has_many,
            belongs_to:  $this->belongs_to,
        );
    }

    /**
     * When "A" has a reference to "B", we say "A" belongs to "B".
     *
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord class.
     * @param non-empty-string|null $as
     *     The name of the accessor.
     */
    public function belongs_to(
        string $associate,
        string $local_key = null,
        string $as = null,
    ): self {
        $this->belongs_to[] = new TransientBelongsToAssociation(
            associate: $associate,
            local_key: $local_key,
            as: $as,
        );

        return $this;
    }

    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord.
     * @param non-empty-string|null $as
     *     The name of the accessor.
     * @param class-string<ActiveRecord>|null $through
     *     An optional ActiveRecord pivot.
     *
     * @return $this
     */
    public function has_many(
        string $associate,
        string $local_key = null,
        string $foreign_key = null,
        string $as = null,
        string $through = null,
    ): self {
        $this->has_many[] = new TransientHasManyAssociation(
            associate: $associate,
            local_key: $local_key,
            foreign_key: $foreign_key,
            as: $as,
            through: $through,
        );

        return $this;
    }

    /**
     * @internal
     *
     * @param class-string<ActiveRecord> $activerecord_class
     *
     * @return $this
     */
    public function use_record(string $activerecord_class): self
    {
        $class = new ReflectionClass($activerecord_class);

        foreach ($class->getAttributes(HasMany::class) as $attribute) {
            $attribute = $attribute->newInstance();

            /** @var HasMany $attribute */
            $this->has_many(
                associate: $attribute->associate,
                foreign_key: $attribute->foreign_key,
                as: $attribute->as,
                through: $attribute->through,
            );
        }

        // note: belongs_to is configured with a schema.

        return $this;
    }

    /**
     * @internal
     *
     * @return $this
     */
    public function use_schema(ActiveRecord\Schema $schema): self
    {
        foreach ($schema->columns as $local_key => $column) {
            if (!$column instanceof BelongsTo) {
                continue;
            }

            $this->belongs_to(
                associate: $column->associate,
                local_key: $local_key,
                as: $column->as ?? $this->resolve_belong_to_accessor($local_key),
            );
        }

        return $this;
    }

    /**
     * @param non-empty-string $local_key
     *
     * @return non-empty-string
     */
    private function resolve_belong_to_accessor(string $local_key): string
    {
        $local_key = trim_suffix($local_key, '_id');

        assert($local_key !== '');

        return $local_key;
    }
}
