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

use function ICanBoogie\pluralize;

/**
 * Representation of the one-to-many relation.
 */
class HasManyRelation extends Relation
{
    /**
     * @inheritdoc
     *
     * @return Query<ActiveRecord>
     */
    public function __invoke(ActiveRecord $record): Query
    {
        return $this
            ->resolve_related()
            ->where([ $this->foreign_key => $record->{$this->local_key} ]);
    }

    protected function resolve_property_name(Model|string $related): string
    {
        return pluralize(parent::resolve_property_name($related));
    }
}
