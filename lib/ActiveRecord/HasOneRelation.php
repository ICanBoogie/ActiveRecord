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
            return $loaded;
        }

        /** @var ActiveRecord|false $one */
        $one = $this->query($record)->one;

        return $one ?: null;
    }

    /**
     * A relation through a pivot needs the key of the pivot to tell which owner a record belongs to.
     */
    #[\Override]
    public function is_preloadable(): bool
    {
        return $this->through === null;
    }

    /**
     * Loads the related records with one query, whatever the number of owners.
     *
     * An owner without a related record is loaded with `null`, otherwise reading it would query.
     * If there are many rows for an owner, the first one is kept, as the getter does.
     *
     * @inheritdoc
     */
    #[\Override]
    public function preload(array $records): void
    {
        $this->is_preloadable() or throw new RelationNotPreloadable($this);

        $owners_by_key = [];

        foreach ($records as $record) {
            $key = $this->read_key($record, $this->local_key);

            if (!$key || $this->has_loaded($record)) {
                continue;
            }

            $owners_by_key[$key][] = $record;
        }

        if (!$owners_by_key) {
            return;
        }

        $by_key = [];

        /** @var ActiveRecord[] $related */
        $related = $this->resolve_related_model()
            ->where([ $this->foreign_key => array_keys($owners_by_key) ])
            ->all;

        foreach ($related as $related_record) {
            $key = $this->read_key($related_record, $this->foreign_key);

            if ($key !== null) {
                $by_key[$key] ??= $related_record;
            }
        }

        foreach ($owners_by_key as $key => $owners) {
            foreach ($owners as $owner) {
                $this->set_loaded($owner, $by_key[$key] ?? null);
            }
        }
    }
}
