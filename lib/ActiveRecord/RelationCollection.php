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

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\OffsetNotWritable;

use function is_array;

/**
 * Relation collection of a model.
 *
 * @property-read Model $model The parent model.
 */
class RelationCollection implements \ArrayAccess
{
    use AccessorTrait;

    /**
     * Relations.
     *
     * @var Relation[]
     */
    private $relations;

    /**
     * @param Model $model The parent model.
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
        if (!$this->offsetExists($offset)) {
            throw new RelationNotDefined($offset, $this);
        }

        return $this->relations[$offset];
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
     * Add a _belongs to_ relation.
     *
     * <pre>
     * $cars->belongs_to([ $drivers, $brands ]);
     * # or
     * $cars->belongs_to([ 'drivers', 'brands' ]);
     * # or
     * $cars->belongs_to($drivers, $brands);
     * # or
     * $cars->belongs_to($drivers)->belongs_to($brands);
     * # or
     * $cars->belongs_to([
     *
     *     [ $drivers, [ 'local_key' => 'card_id', 'foreign_key' => 'driver_id' ] ]
     *     [ $brands, [ 'local_key' => 'brand_id', 'foreign_key' => 'brand_id' ] ]
     *
     * ]);
     * </pre>
     *
     * @param string|array $belongs_to
     */
    public function belongs_to($belongs_to): Model
    {
        if (\func_num_args() > 1) {
            $belongs_to = \func_get_args();
        }

        foreach ((array) $belongs_to as $definition) {
            if (!is_array($definition)) {
                $definition = [ $definition ];
            }

            list($related, $options) = ((array) $definition) + [ 1 => [] ];

            $relation = new BelongsToRelation($this->model, $related, $options);

            $this->relations[$relation->as] = $relation;
        }

        return $this->model;
    }

    /**
     * Add a one-to-many relation.
     *
     * <pre>
     * $this->has_many('comments');
     * $this->has_many([ 'comments', 'attachments' ]);
     * $this->has_many([ [ 'comments', [ 'as' => 'comments' ] ], 'attachments' ]);
     * </pre>
     *
     * @param Model|string|array $related The related model can be specified using its instance or its
     * identifier.
     * @param array $options the following options are available:
     *
     * - `local_key`: The name of the local key. Default: The parent model's primary key.
     * - `foreign_key`: The name of the foreign key. Default: The parent model's primary key.
     * - `as`: The name of the magic property to add to the prototype. Default: a plural name
     * resolved from the foreign model's id.
     *
     * @see HasManyRelation
     */
    public function has_many($related, array $options = []): ?Model
    {
        if (is_array($related)) {
            $relation_list = $related;

            foreach ($relation_list as $definition) {
                [ $related, $options ] = ((array) $definition) + [ 1 => [] ];

                $relation = new HasManyRelation($this->model, $related, $options);

                $this->relations[$relation->as] = $relation;
            }

            return null;
        }

        $relation = new HasManyRelation($this->model, $related, $options);

        $this->relations[$relation->as] = $relation;

        return $this->model;
    }
}
