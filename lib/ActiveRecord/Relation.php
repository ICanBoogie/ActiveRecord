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
use ICanBoogie\ActiveRecord;
use ICanBoogie\Prototype;
use RuntimeException;

use function array_pop;
use function explode;
use function is_string;

/**
 * Representation of a relation.
 *
 * @property-read Model $related The related model of the relation.
 */
abstract class Relation
{
    /**
     * @uses get_related
     */
    use AccessorTrait;

    private function get_related(): Model
    {
        return $this->resolve_related();
    }

    /**
     * The name of the relation.
     */
    public readonly string $as;

    /**
     * Local key. Default: The parent model's primary key.
     */
    public readonly string $local_key;

    /**
     * Foreign key. Default: The parent model's primary key.
     */
    public readonly string $foreign_key;

    /**
     * @param Model $owner
     *     The parent model of the relation.
     * @param Model|string $related
     *     The related model of the relation. Can be specified using its instance or its identifier.
     * @param array{
     *     as?: string,
     *     local_key?: string,
     *     foreign_key?: string,
     * } $options
     *
     *     Where:
     *     - `as`: The name of the magic property to add to the prototype. Default: a plural name
     * resolved from the foreign model's id.
     *     - `local_key`: The name of the local key. Default: The parent model's primary key.
     *     - `foreign_key`: The name of the foreign key. Default: The parent model's primary key.
     */
    public function __construct(
        public readonly Model $owner,
        private readonly Model|string $related,
        array $options = []
    ) {
        $this->as = $options['as'] ?? $this->resolve_property_name($related);
        $this->local_key = $options['local_key']
            ?? $owner->primary
            ?? throw new RuntimeException("Unable to determine 'local_key' from parent '$owner->id', primary key is null");
        $this->foreign_key = $options['foreign_key']
            ?? $owner->primary
            ?? throw new RuntimeException("Unable to determine 'foreign_key' from parent '$owner->id', primary key is null");

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
    protected function resolve_property_name(Model|string $related): string
    {
        $related_id = $related instanceof Model ? $related->id : $related;
        $parts = explode('.', $related_id);

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
