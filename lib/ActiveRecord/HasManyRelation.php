<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;

/**
 * Representation of a has_many relation.
 */
final readonly class HasManyRelation extends HasRelation
{
    /**
     * @inheritdoc
     *
     * @return Query<ActiveRecord>
     */
    public function __invoke(ActiveRecord $record): Query
    {
        return $this->query($record);
    }

    protected function resolve_property_name(string $related): string
    {
        return Inflector::pluralize(parent::resolve_property_name($related));
    }
}
