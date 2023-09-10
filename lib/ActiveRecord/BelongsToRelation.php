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
use LogicException;

use function array_pop;
use function explode;
use function ICanBoogie\singularize;

/**
 * Representation of a belongs_to relation.
 */
class BelongsToRelation extends Relation
{
    /**
     * @inheritdoc
     */
    public function __invoke(ActiveRecord $record): ?ActiveRecord
    {
        $local_key = $this->local_key;
        $id = $record->{$local_key} ?? null;

        if (!$id) {
            if ($this->owner->schema->columns[$local_key]->null) {
                return null;
            }

            throw new LogicException("Unable to establish relation, '$local_key' is empty.");
        }

        return $this->resolve_related_model()[$id];
    }

    /**
     * Adds a setter for the property to update the local key.
     *
     * @inheritdoc
     */
    protected function alter_prototype(Prototype $prototype, string $property): void
    {
        parent::alter_prototype($prototype, $property);

        $prototype["set_$property"] = function (ActiveRecord $record, ActiveRecord $related) {
            $record->{$this->local_key} = $related->{$this->foreign_key};
        };
    }

    /**
     * @inheritdoc
     */
    protected function resolve_property_name(string $related): string
    {
        $parts = explode('.', $related);
        $part = array_pop($parts);

        return singularize($part);
    }
}
