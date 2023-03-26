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
 */
class RelationCollection implements ArrayAccess
{
    /**
     * Relations.
     *
     * @var array<string, Relation>
     */
    private array $relations;

    /**
     * @param Model<int|string|string[], ActiveRecord<int|string|string[]>> $model The parent model.
     */
    public function __construct(
        public readonly Model $model
    ) {
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
     * @param (Closure(Relation, string $as): ?Relation) $predicate
     *
     * @return Relation|null
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
