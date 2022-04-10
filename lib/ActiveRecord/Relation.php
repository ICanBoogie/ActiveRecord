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

use function array_pop;
use function explode;

/**
 * Representation of a relation.
 *
 * @property-read Model $parent The parent model of the relation.
 * @property-read Model $related The related model of the relation.
 * @property-read string $as The name of the relation.
 * @property-read string $local_key The local key.
 * @property-read string $foreign_key The foreign key.
 */
abstract class Relation
{
    /**
     * @uses get_parent
     * @uses get_related
     * @uses get_as
     * @uses get_local_key
     * @uses get_foreign_key
     */
    use AccessorTrait;

    private function get_parent(): Model
    {
        return $this->parent;
    }

    private function get_related(): Model
    {
        $related = $this->related;

        if ($related instanceof Model) {
            return $related;
        }

        return $this->related = $this->parent->models[$related];
    }

    /**
     * The name of the relation.
     */
    private string $as;

    private function get_as(): string
    {
        return $this->as;
    }

    /**
     * Local key. Default: The parent model's primary key.
     *
     * @var string
     */
    private $local_key;

    private function get_local_key(): string
    {
        return $this->local_key;
    }

    /**
     * Foreign key. Default: The parent model's primary key.
     *
     * @var string
     */
    private $foreign_key;

    private function get_foreign_key(): string
    {
        return $this->foreign_key;
    }

    /**
     * @param Model $parent The parent model of the relation.
     * @param Model|string $related The related model of the relation. Can be specified using its
     * instance or its identifier.
     * @param array<string, mixed> $options the following options are available:
     *
     * - `as`: The name of the magic property to add to the prototype. Default: a plural name
     * resolved from the foreign model's id.
     * - `local_key`: The name of the local key. Default: The parent model's primary key.
     * - `foreign_key`: The name of the foreign key. Default: The parent model's primary key.
     */
    public function __construct(
        private Model $parent,
        private Model|string $related,
        array $options = []
    ) {
        $this->as = $options['as'] ?? $this->resolve_property_name($related);
        $this->local_key = $options['local_key'] ?? $parent->primary;
        $this->foreign_key = $options['foreign_key'] ?? $parent->primary;

        $activerecord_class = $this->resolve_activerecord_class($parent);
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
        $related = $this->related;

        if ($related instanceof Model) {
            return $related;
        }

        return $this->related = $this->parent->models[$related];
    }
}
