<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use InvalidArgumentException;

use function is_string;

/**
 * What a has-many and a has-one relation have in common: the related records reference the owner,
 * directly or through a pivot, and they are found with the same query.
 */
abstract readonly class HasRelation extends Relation
{
    /**
     * @inheritdoc
     *
     * @param Model<*> $owner
     * @param non-empty-string $foreign_key
     *     The column that references the owner: on the related record, or on the pivot with
     *     `$through`.
     * @param class-string<ActiveRecord>|null $through
     *     The ActiveRecord used as pivot.
     * @param non-empty-string|null $through_key
     *     The column of the pivot that references the related record, required with `$through`.
     *
     * @throws InvalidArgumentException if `$through_key` is given without `$through`, or the other way round.
     */
    public function __construct(
        Model $owner,
        string $related,
        string $foreign_key,
        string $as,
        public ?string $through = null,
        public ?string $through_key = null,
    ) {
        assert(is_string($owner->primary));

        ($through === null) === ($through_key === null)
        or throw new InvalidArgumentException("`through` and `through_key` go together");

        parent::__construct(
            owner: $owner,
            related: $related,
            local_key: $owner->primary,
            foreign_key: $foreign_key,
            as: $as,
        );
    }

    /**
     * Creates a query for the records related to a record.
     *
     * @return Query<ActiveRecord>
     */
    protected function query(ActiveRecord $record): Query
    {
        if ($this->through && $this->through_key) {
            return $this->build_through_query($record, $this->through, $this->through_key);
        }

        return $this
            ->resolve_related_model()
            ->where([ $this->foreign_key => $record->{$this->local_key} ]);
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
     * What an owner is loaded with, including when it has no related record, is up to
     * {@see into_loaded()}: reading the relation of an owner that was preloaded never queries.
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
                $by_key[$key][] = $related_record;
            }
        }

        foreach ($owners_by_key as $key => $owners) {
            $loaded = $this->into_loaded($by_key[$key] ?? []);

            foreach ($owners as $owner) {
                $this->set_loaded($owner, $loaded);
            }
        }
    }

    /**
     * Turns the records related to an owner into the value that is loaded for it.
     *
     * @param list<ActiveRecord> $related The records related to the owner, empty if there are none.
     *
     * @return ActiveRecord|list<ActiveRecord>|null
     */
    abstract protected function into_loaded(array $related): ActiveRecord|array|null;

    /**
     * The related records are joined to the pivot, which is filtered on the key of the owner: the
     * owner's table isn't needed. Both columns of the pivot are resolved by the config.
     *
     * The default select is kept: it has the columns of the related record and of its parents, and
     * none of the pivot.
     *
     * @param class-string<ActiveRecord> $through
     * @param non-empty-string $through_key
     *
     * @return Query<ActiveRecord>
     *
     * https://guides.rubyonrails.org/association_basics.html#the-has-many-through-association
     */
    private function build_through_query(ActiveRecord $record, string $through, string $through_key): Query
    {
        $related = $this->resolve_related_model();

        assert(is_string($related->primary));

        $quote = $related->connection->quote_identifier(...);
        $pivot_name = $quote($this->model_for_activerecord($through)->name);

        return $related
            ->query()
            ->join(expression: "INNER JOIN $pivot_name"
                . " ON $pivot_name." . $quote($through_key)
                . ' = ' . $quote($related->alias) . '.' . $quote($related->primary))
            ->where("$pivot_name." . $quote($this->foreign_key) . ' = ?', $record->{$this->local_key});
    }
}
