<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema;
use ReflectionClass;

final class AssociationBuilder
{
    /**
     * @var array<TransientHasManyAssociation>
     */
    private array $has_many = [];

    public function build(): TransientAssociation
    {
        return new TransientAssociation(
            has_many:  $this->has_many,
        );
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
        string $foreign_key = null,
        string $as = null,
        string $through = null,
    ): self {
        $this->has_many[] = new TransientHasManyAssociation(
            associate: $associate,
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

        foreach ($class->getAttributes(Schema\HasMany::class) as $attribute) {
            $attribute = $attribute->newInstance();
            /** @var Schema\HasMany $attribute */

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
