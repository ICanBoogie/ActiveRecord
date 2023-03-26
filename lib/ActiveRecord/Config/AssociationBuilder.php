<?php

namespace ICanBoogie\ActiveRecord\Config;

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
     * @param class-string|string $associate
     *     The associate ActiveRecord class or model identifier.
     * @param non-empty-string|null $as
     *     The name of the accessor.
     * @param class-string|string|null $through
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
}
