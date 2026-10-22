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
        /** @var ActiveRecord|false $one */
        $one = $this->query($record)->one;

        return $one ?: null;
    }
}
