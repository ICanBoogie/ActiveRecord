<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\Prototype;

/**
 * Representation of a has_many relation.
 *
 * The getter returns a {@see Query}, whatever the relation was preloaded or not. The records loaded
 * by {@see preload()} are read with the `{name}_loaded` getter, e.g. `$post->comments_loaded`.
 */
final readonly class HasManyRelation extends HasRelation
{
    /**
     * @inheritdoc
     *
     * @return Query<ActiveRecord>
     */
    public function __invoke(ActiveRecord $record): Query
    {
        return $this->query($record);
    }

    /**
     * An owner without a related record is loaded with an empty list.
     *
     * @return list<ActiveRecord>
     */
    #[\Override]
    protected function into_loaded(array $related): array
    {
        return $related;
    }

    /**
     * Adds the `{name}_loaded` getter, which returns the records loaded by {@see preload()}, or
     * fetches them when the relation wasn't preloaded for the record.
     *
     * @inheritdoc
     */
    #[\Override]
    protected function alter_prototype(Prototype $prototype, string $property): void
    {
        parent::alter_prototype($prototype, $property);

        $prototype["get_{$property}_loaded"] = function (ActiveRecord $record): array {
            if ($this->has_loaded($record, $loaded)) {
                assert(is_array($loaded));

                return $loaded;
            }

            /** @var list<ActiveRecord> */
            return $this->query($record)->all;
        };
    }

    protected function resolve_property_name(string $related): string
    {
        return Inflector::pluralize(parent::resolve_property_name($related));
    }
}
