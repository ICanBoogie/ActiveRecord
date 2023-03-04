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
 * Representation of the one-to-many or many-to-many relation.
 */
class HasManyRelation extends Relation
{
    public readonly ?string $through;

    /**
     * @param Model $owner
     * @param Model|string $related
     * @param array{
     *     as?: string,
     *     local_key?: string,
     *     foreign_key?: string,
     *     through?: string,
     * } $options
     */
    public function __construct(Model $owner, Model|string $related, array $options = [])
    {
        $this->through = $options['through'] ?? null;

        parent::__construct($owner, $related, $options);
    }

    /**
     * @inheritdoc
     *
     * @return Query<ActiveRecord>
     */
    public function __invoke(ActiveRecord $record): Query
    {
        if ($this->through) {
            return $this->build_through_query($record, $this->through);
        }

        return $this
            ->resolve_related()
            ->where([ $this->foreign_key => $record->{$this->local_key} ]);
    }

    /**
     * @return Query<ActiveRecord>
     *
     * https://guides.rubyonrails.org/association_basics.html#the-has-many-through-association
     */
    private function build_through_query(ActiveRecord $record, string $through_id): Query
    {
        // $owner == $r1_model
        // $related === $r2_model

        $owner = $this->owner;
        $related = $this->resolve_related();
        $through = $this->ensure_model($through_id);
        $r = $through->relations;
        $r1 = $r[$this->owner->id];
        $r2 = $r[$related->id];
        $r2_model = $this->ensure_model($r2->related);

        $q = $related->select("`{alias}`.*");
        $q->join("INNER JOIN `{$through->name}` ON `{$through->name}`.{$r2->local_key} = `{$r2_model->alias}`.{$related->primary}");
        $q->join("INNER JOIN `{$owner->name}` `{$owner->alias}` ON `{$through->name}`.{$r1->local_key} = `{$owner->alias}`.{$owner->primary}");
        $q->where("`{$owner->alias}`.{$owner->primary} = ?", $record->{$this->local_key});

        return $q;
    }

    protected function resolve_property_name(Model|string $related): string
    {
        return pluralize(parent::resolve_property_name($related));
    }
}
