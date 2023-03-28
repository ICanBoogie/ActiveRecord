<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\SchemaAttribute;

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
     * @param class-string|string $associate
     *     The associate ActiveRecord class or model identifier.
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
     * @param class-string|non-empty-string $associate
     *     The associate ActiveRecord class or model identifier.
     * @param non-empty-string|null $as
     *     The name of the accessor.
     * @param class-string|non-empty-string|null $through
     *     The pivot ActiveRecord class or model identifier.
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
     * @param SchemaAttribute[] $class_attributes
     *
     * @return $this
     */
    public function from_attributes(array $class_attributes): self
    {
        foreach ($class_attributes as $attribute) {
            if (!$attribute instanceof HasMany) {
                continue;
            }

            $this->has_many(
                associate: $attribute->associate,
                foreign_key: $attribute->foreign_key,
                as: $attribute->as,
                through: $attribute->through,
            );
        }

        return $this;
    }
}
