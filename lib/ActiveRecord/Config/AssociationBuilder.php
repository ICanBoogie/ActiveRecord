<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema;
use LogicException;
use ReflectionClass;

/**
 * Builds associations between records.
 */
final class AssociationBuilder
{
    /**
     * @var list<TransientHasManyAssociation>
     */
    private array $has_many = [];

    /**
     * @param class-string<ActiveRecord>|null $record_class
     *     The initial associations are configured from the {@see ActiveRecord} class, when it is provided.
     *
     * @throws LogicException if the {@see $record_class} is not an {@see ActiveRecord} class.
     * @throws InvalidAssociation if the attributes of the record define an invalid association.
     */
    public function __construct(
        public readonly ?string $record_class = null,
    ) {
        if ($record_class) {
            Assert::extends_activerecord($record_class);

            $this->use_record($record_class);
        }
    }

    public function build(): TransientAssociation
    {
        return new TransientAssociation(
            has_many: $this->has_many,
        );
    }

    /**
     * @throws InvalidAssociation
     */
    private function error(string $message): never
    {
        throw new InvalidAssociation($message, $this->record_class);
    }

    /**
     * @param class-string<ActiveRecord> $associate
     *     The associate ActiveRecord.
     * @param non-empty-string|null $foreign_key
     *     The column of the associate that references the owner. Defaults to the associate's
     *     {@see Schema\BelongsTo} column that references the owner, or one of its ancestors.
     * @param non-empty-string|null $as
     *     The name of the accessor.
     * @param class-string<ActiveRecord>|null $through
     *     An optional ActiveRecord pivot.
     *
     * @return $this
     *
     * @throws InvalidAssociation if the associate or the pivot is not an {@see ActiveRecord} class, if
     * `$foreign_key` or `$as` is empty, or if the relation is already defined.
     */
    public function has_many(
        string $associate,
        ?string $foreign_key = null,
        ?string $as = null,
        ?string $through = null,
    ): self {
        is_subclass_of($associate, ActiveRecord::class)
        or $this->error("`$associate` is not extending `" . ActiveRecord::class . "`, given for `associate`");

        $through === null || is_subclass_of($through, ActiveRecord::class)
        or $this->error("`$through` is not extending `" . ActiveRecord::class . "`, given for `through`");

        // @phpstan-ignore-next-line // double checking, just in case
        $foreign_key !== ''
        or $this->error("`foreign_key` must not be empty");

        // @phpstan-ignore-next-line // double checking, just in case
        $as !== ''
        or $this->error("`as` must not be empty");

        foreach ($this->has_many as $other) {
            if (
                $other->associate === $associate
                && $other->through === $through
                && $other->foreign_key === $foreign_key
                && $other->as === $as
            ) {
                $this->error("The has-many relation with `$associate` is already defined");
            }
        }

        $this->has_many[] = new TransientHasManyAssociation(
            associate: $associate,
            foreign_key: $foreign_key,
            as: $as,
            through: $through,
        );

        return $this;
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     */
    private function use_record(string $activerecord_class): void
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
    }
}
