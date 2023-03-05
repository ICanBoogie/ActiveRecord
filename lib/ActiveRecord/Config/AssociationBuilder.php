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

    public function belongs_to(
        string $model_id,
        string $local_key = null,
        string $foreign_key = null,
        string $as = null,
    ): self {
        $this->belongs_to[] = new TransientBelongsToAssociation(
            model_id: $model_id,
            local_key: $local_key,
            foreign_key: $foreign_key,
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
