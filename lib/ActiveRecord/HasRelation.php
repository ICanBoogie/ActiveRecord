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
