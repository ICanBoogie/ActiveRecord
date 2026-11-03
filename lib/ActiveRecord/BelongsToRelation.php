<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\Prototype;
use LogicException;

use function array_pop;
use function explode;

/**
 * Representation of a belongs_to relation.
 */
final readonly class BelongsToRelation extends Relation
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

        $local_key = $this->local_key;
        $id = $record->{$local_key} ?? null;

        if (!$id) {
            if ($this->owner->schema->columns[$local_key]->null) {
                return null;
            }

            throw new LogicException("Unable to establish relation, '$local_key' is empty.");
        }

        $related = $this->resolve_related_model()->find_one($id); // @phpstan-ignore argument.type

        // Kept for the next read, which would otherwise query again. The key it was loaded with is kept too: if the
        // key of the record changes, the related record is loaded again.
        $this->set_loaded($record, $related);

        return $related;
    }

    #[\Override]
    public function is_preloadable(): bool
    {
        return true;
    }

    /**
     * Loads the related records with one query, whatever the number of owners.
     *
     * Owners without a key are skipped: the getter returns `null` for them without a query. A key
     * that matches no row is skipped too, so that the getter fails as it does when it isn't
     * preloaded, with {@see RecordNotFound}, instead of the preload failing for all the owners.
     *
     * Owners that reference the same record share the instance.
     *
     * @inheritdoc
     */
    #[\Override]
    public function preload(array $records): void
    {
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

        /** @var ActiveRecord[] $related */
        $related = $this->resolve_related_model()
            ->where([ $this->foreign_key => array_keys($owners_by_key) ])
            ->all;

        foreach ($related as $related_record) {
            $key = $this->read_key($related_record, $this->foreign_key);

            foreach ($owners_by_key[$key] ?? [] as $owner) {
                $this->set_loaded($owner, $related_record);
            }
        }
    }

    /**
     * Adds a setter for the property to update the local key.
     *
     * @inheritdoc
     */
    protected function alter_prototype(Prototype $prototype, string $property): void
    {
        parent::alter_prototype($prototype, $property);

        $prototype["set_$property"] = function (ActiveRecord $record, ActiveRecord $related) {
            $record->{$this->local_key} = $related->{$this->foreign_key};
        };
    }

    /**
     * @inheritdoc
     */
    protected function resolve_property_name(string $related): string
    {
        $parts = explode('.', $related);
        $part = array_pop($parts);

        return Inflector::singularize($part);
    }
}
