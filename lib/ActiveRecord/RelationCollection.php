<?php

namespace ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\Association;

use function array_values;
use function count;
use function is_a;
use function is_string;
use function reset;

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
     * @return array<string, Relation>
     *     Where _key_ is a getter name; for example, 'comments'
     */
    public function all(): array
    {
        return $this->relations;
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

    /**
     * Resolve the columns linking the model to another model.
     *
     * The relations of the model to the other model, and the relations of the other model to the
     * model, are considered. `has_many … through` relations are ignored.
     *
     * @param Model<ActiveRecord> $other
     *
     * @return array{ non-empty-string, non-empty-string }
     *     The column of the model, and the column of the other model.
     *
     * @throws RelationNotFound if no relation links the models.
     * @throws AmbiguousRelation if more than one relation links the models.
     */
    public function columns_to(Model $other): array
    {
        // Where _key_ identifies the column pair, so that a relation and its inverse count once.
        $columns = [];
        $relations = [];

        foreach ($this->relations as $relation) {
            if (!$this->is_direct($relation) || !is_a($other->activerecord_class, $relation->related, true)) {
                continue;
            }

            $key = "$relation->local_key $relation->foreign_key";
            $columns[$key] ??= [ $relation->local_key, $relation->foreign_key ];
            $relations[$key] ??= $relation;
        }

        foreach ($other->relations->all() as $relation) {
            if (!$this->is_direct($relation) || !is_a($this->model->activerecord_class, $relation->related, true)) {
                continue;
            }

            $key = "$relation->foreign_key $relation->local_key";
            $columns[$key] ??= [ $relation->foreign_key, $relation->local_key ];
            $relations[$key] ??= $relation;
        }

        return match (count($columns)) {
            0 => throw new RelationNotFound($this->model, $other),
            1 => reset($columns),
            default => throw new AmbiguousRelation($this->model, $other, array_values($relations)),
        };
    }

    private function is_direct(Relation $relation): bool
    {
        return !($relation instanceof HasManyRelation && $relation->through);
    }
}
