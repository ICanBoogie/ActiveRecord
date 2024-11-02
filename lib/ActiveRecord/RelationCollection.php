<?php

namespace ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\Config\Association;

use function is_string;

/**
 * Relation collection of a model.
 */
class RelationCollection
{
    /**
     * @var array<string, Relation>
     *     Where _key_ is a getter name; for example, 'comments'
     */
    private readonly array $relations;

    public function __construct(
        public readonly Model $model,
        ?Association $association
    ) {
        $relations = [];

        if ($association) {
            foreach ($association->belongs_to as $r) {
                $as = $r->as;
                $relations[$as] = new BelongsToRelation(
                    owner: $this->model,
                    related: $r->associate,
                    local_key: $r->local_key,
                    foreign_key: $r->foreign_key,
                    as: $as,
                );
            }

            if ($association->has_many) {
                assert(is_string($this->model->primary));
            }

            foreach ($association->has_many as $r) {
                $as = $r->as;
                $relations[$as] = new HasManyRelation(
                    owner: $this->model,
                    related: $r->associate,
                    foreign_key: $r->foreign_key,
                    as: $as,
                    through: $r->through,
                );
            }
        }

        $this->relations = $relations;
    }

    /**
     * Whether a relation exists.
     *
     * @param non-empty-string $relation_name
     */
    public function has(string $relation_name): bool
    {
        return isset($this->relations[$relation_name]);
    }

    /**
     * Returns an existing relation.
     *
     * @param non-empty-string $relation_name
     *
     * @throws RelationNotDefined if the relation doesn't exist.
     */
    public function get(string $relation_name): ?Relation
    {
        return $this->relations[$relation_name]
            ?? throw new RelationNotDefined($relation_name, $this);
    }

    /**
     * @param (Closure(Relation, string $as): bool) $predicate
     */
    public function find(Closure $predicate): ?Relation
    {
        foreach ($this->relations as $as => $relation) {
            if ($predicate($relation, $as)) {
                return $relation;
            }
        }

        return null;
    }
}
