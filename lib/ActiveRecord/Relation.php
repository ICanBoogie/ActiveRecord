<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\Prototype;
use WeakMap;

use function array_pop;
use function explode;
use function is_int;
use function is_string;

/**
 * Representation of a relation.
 */
abstract readonly class Relation
{
    /**
     * The values loaded by {@see preload()}, by owner record. An entry holds the key of the owner
     * that the value was loaded for, and the value, which can be `null`.
     *
     * Entries are keyed by record identity and go away with the record.
     *
     * @var WeakMap<ActiveRecord, array{ int|non-empty-string|null, ActiveRecord|null }>
     */
    private WeakMap $loaded;

    /**
     * @param Model<*> $owner
     *     The owner of the relation.
     * @param class-string<ActiveRecord> $related
     *     The class of the related ActiveRecord.
     * @param non-empty-string $local_key
     *     The name of the column on the owner model.
     * @param non-empty-string $foreign_key
     *     The name of the column on the foreign model.
     */
    public function __construct(
        public Model $owner,
        public string $related,
        public string $local_key,
        public string $foreign_key,
        public string $as,
    ) {
        $this->loaded = new WeakMap();

        $prototype = Prototype::from($this->owner->activerecord_class);
        $this->alter_prototype($prototype, $this->as);
    }

    /**
     * Create a query with the relation.
     *
     * A relation that supports {@see preload()} returns what was loaded for the record, see
     * {@see has_loaded()}, before it queries.
     */
    abstract public function __invoke(ActiveRecord $record): mixed;

    /**
     * Whether the relation can be loaded for many records at once with {@see preload()}.
     */
    public function is_preloadable(): bool
    {
        return false;
    }

    /**
     * Loads the relation of many records at once, so that reading it from each of them doesn't query.
     *
     * Records that already have the relation loaded are skipped, and no query is made when none
     * remains.
     *
     * @param ActiveRecord[] $records The owners.
     *
     * @throws RelationNotPreloadable if the relation can't be preloaded, see {@see is_preloadable()}.
     */
    public function preload(array $records): void
    {
        throw new RelationNotPreloadable($this);
    }

    /**
     * Whether the value of the relation was loaded for a record, and is still valid.
     *
     * An entry is stale when the key of the record changed since it was loaded, e.g. a foreign key
     * that was assigned another value: the relation is then not considered loaded.
     *
     * @param ActiveRecord|null $value Receives the loaded value, which can be `null`.
     */
    protected function has_loaded(ActiveRecord $record, ?ActiveRecord &$value = null): bool
    {
        $entry = $this->loaded[$record] ?? null;

        if ($entry === null || $entry[0] !== $this->read_key($record, $this->local_key)) {
            return false;
        }

        $value = $entry[1];

        return true;
    }

    /**
     * Stores the value loaded for a record, with the current key of the record.
     */
    protected function set_loaded(ActiveRecord $record, ?ActiveRecord $value): void
    {
        $this->loaded[$record] = [ $this->read_key($record, $this->local_key), $value ];
    }

    /**
     * Reads a key from a record: the value of a column that links the owner to the related record.
     *
     * @param non-empty-string $column
     *
     * @return int|non-empty-string|null `null` when the key is not set, or empty.
     */
    protected function read_key(ActiveRecord $record, string $column): int|string|null
    {
        $key = $record->{$column} ?? null;

        return (is_int($key) || is_string($key)) && $key !== '' ? $key : null;
    }

    /**
     * Add a getter for the relation to the prototype.
     *
     * @param Prototype $prototype The active record prototype.
     * @param string $property The name of the property.
     */
    protected function alter_prototype(Prototype $prototype, string $property): void
    {
        $prototype["get_$property"] = $this;
    }

    /**
     * Resolve the property name from the related model.
     */
    protected function resolve_property_name(string $related): string
    {
        $parts = explode('.', $related);

        return array_pop($parts);
    }

    /**
     * Resolve the related model.
     *
     * @return Model<*>
     */
    protected function resolve_related_model(): Model
    {
        return $this->model_for_activerecord($this->related);
    }

    /**
     * @template T of ActiveRecord
     *
     * @param class-string<T> $activerecord_class
     *
     * @return Model<T>
     */
    protected function model_for_activerecord(string $activerecord_class): Model
    {
        return $this->owner->models->model_for_record($activerecord_class);
    }
}
