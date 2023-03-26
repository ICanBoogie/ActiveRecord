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
     *     A model class or identifier.
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

    public function has_many(
        string $model_id,
        string $local_key = null,
        string $foreign_key = null,
        string $as = null,
        string $through = null,
    ): self {
        $this->has_many[] = new TransientHasManyAssociation(
            model_id: $model_id,
            local_key: $local_key,
            foreign_key: $foreign_key,
            as: $as,
            through: $through
        );

        return $this;
    }
}
