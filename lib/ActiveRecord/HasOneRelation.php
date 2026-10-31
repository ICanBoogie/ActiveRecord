<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;

/**
 * Representation of a has_one relation.
 *
 * The getter returns the first related record, or `null` if there's none. That there is at most
 * one is up to the schema, e.g. with a unique key on the column that references the owner.
 */
final readonly class HasOneRelation extends HasRelation
{
    /**
     * @inheritdoc
     */
    public function __invoke(ActiveRecord $record): ?ActiveRecord
    {
        if ($this->has_loaded($record, $loaded)) {
            /** @var ActiveRecord|null $loaded Only records are loaded for this relation. */
            return $loaded;
        }

        /** @var ActiveRecord|false $one */
        $one = $this->query($record)->one;

        return $one ?: null;
    }

    /**
     * An owner without a related record is loaded with `null`. If there are many rows for an owner,
     * the first one is kept, as the getter does.
     */
    #[\Override]
    protected function into_loaded(array $related): ?ActiveRecord
    {
        return $related[0] ?? null;
    }
}
