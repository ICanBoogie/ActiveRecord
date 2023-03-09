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

use ICanBoogie\ActiveRecord;
use ICanBoogie\Prototype;

use function array_pop;
use function explode;

/**
 * Representation of a relation.
 */
abstract class Relation
{
    /**
     * @param Model<int|string|string[], ActiveRecord> $owner
     *     The parent model of the relation.
     * @param string $related
     *     The related model of the relation. Can be specified using its instance or its identifier.
     * @param string $local_key
     *     The name of the column on the owner model.
     * @param string $foreign_key
     *     The name of the column on the foreign model.
     */
    public function __construct(
        public readonly Model $owner,
        public readonly string $related,
        public readonly string $local_key,
        public readonly string $foreign_key,
        public readonly string $as,
    ) {
        $activerecord_class = $this->resolve_activerecord_class($owner);
        $prototype = Prototype::from($activerecord_class);

        $this->alter_prototype($prototype, $this->as);
    }

    /**
     * Create a query with the relation.
     */
    abstract public function __invoke(ActiveRecord $record): mixed;

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
     * Resolve the active record class name from the specified model.
     *
     * @throws ActiveRecordClassNotValid
     *
     * @return class-string
     */
    protected function resolve_activerecord_class(Model $model): string
    {
        $activerecord_class = $model->activerecord_class;

        if ($activerecord_class === ActiveRecord::class) {
            throw new ActiveRecordClassNotValid(
                $activerecord_class,
                "The Active Record class cannot be 'ICanBoogie\ActiveRecord' for a relationship."
            );
        }

        return $activerecord_class;
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
     */
    protected function resolve_related(): Model
    {
        return $this->ensure_model($this->related);
    }

    protected function ensure_model(Model|string $model_or_id): Model
    {
        if ($model_or_id instanceof Model) {
            return $model_or_id;
        }

        return $this->owner->models->model_for_id($model_or_id);
    }
}
