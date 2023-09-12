<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ArrayAccess;
use Closure;
use ICanBoogie\ActiveRecord;
use ICanBoogie\OffsetNotWritable;

/**
 * Relation collection of a model.
 *
 * @implements ArrayAccess<string, Relation>
 *     Where _key_ is a getter name e.g. 'comments'
 */
class RelationCollection implements ArrayAccess
{
    /**
     * Relations.
     *
     * @var array<string, Relation>
     *     Where _key_ is a getter name e.g. 'comments'
     */
    private array $relations;

    public function __construct(
        public readonly Model $model,
        ?ActiveRecord\Config\Association $association,
    ) {
        $this->apply_association($association);
    }

    private function apply_association(?ActiveRecord\Config\Association $association): void
    {
        if (!$association) {
            return;
        }

        foreach ($association->belongs_to as $r) {
            $this->belongs_to(
                related: $r->associate,
                local_key: $r->local_key,
                foreign_key: $r->foreign_key,
                as: $r->as,
            );
        }

        foreach ($association->has_many as $r) {
            $this->has_many(
                related: $r->associate,
                local_key: $r->local_key,
                foreign_key: $r->foreign_key,
                as: $r->as,
                through: $r->through,
            );
        }
    }

    /**
     * Checks if a relation exists.
     *
     * @param string $offset Relation name.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->relations[$offset]);
    }

    /**
     * Returns a relation.
     *
     * @param string $offset Relation name.
     *
     * @throws RelationNotDefined if the relation is not defined.
     */
    public function offsetGet(mixed $offset): Relation
    {
        return $this->relations[$offset]
            ?? throw new RelationNotDefined($offset, $this);
    }

    /**
     * @throws OffsetNotWritable because relations cannot be set.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new OffsetNotWritable([ $offset, $this ]);
    }

    /**
     * @throws OffsetNotWritable because relations cannot be unset.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new OffsetNotWritable([ $offset, $this ]);
    }

    /**
     * Adds a {@link BelongsToRelation} relation.
     *
     * @param class-string<ActiveRecord> $related
     * @param non-empty-string $local_key
     * @param non-empty-string $foreign_key
     * @param non-empty-string $as
     */
    public function belongs_to(
        string $related,
        string $local_key,
        string $foreign_key,
        string $as,
    ): void {
        $this->relations[$as] = new BelongsToRelation(
            owner: $this->model,
            related: $related,
            local_key: $local_key,
            foreign_key: $foreign_key,
            as: $as,
        );
    }

    /**
     * Adds a {@link HasManyRelation} relation.
     *
     * @param class-string<ActiveRecord> $related
     * @param non-empty-string $local_key
     * @param non-empty-string $foreign_key
     * @param non-empty-string $as
     * @param class-string<ActiveRecord>|null $through
     */
    public function has_many(
        string $related,
        string $local_key,
        string $foreign_key,
        string $as,
        ?string $through = null,
    ): void {
        $this->relations[$as] = new HasManyRelation(
            owner: $this->model,
            related: $related,
            local_key: $local_key,
            foreign_key: $foreign_key,
            as: $as,
            through: $through,
        );
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
