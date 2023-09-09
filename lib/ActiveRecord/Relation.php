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
     * @param Model $owner
     *     The parent model of the relation.
     * @param class-string<Model> $related
     *     The class of the related Model.
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
        ActiveRecord\Config\Assert::extends_model($related);

        $prototype = Prototype::from($this->owner->activerecord_class);
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

    /**
     * @param Model|class-string<Model> $model_or_class
     */
    protected function ensure_model(Model|string $model_or_class): Model
    {
        if ($model_or_class instanceof Model) {
            return $model_or_class;
        }

        return $this->owner->models->model_for_class($model_or_class);
    }
}
