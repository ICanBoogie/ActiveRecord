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
use LogicException;
use PDO;

use function ICanBoogie\pluralize;
use function is_string;

/**
 * Representation of a has_many relation.
 */
class HasManyRelation extends Relation
{
    /**
     * @inheritdoc
     *
     * @param class-string<ActiveRecord>|null $through
     *     The ActiveRecord used as pivot.
     */
    public function __construct(
        Model $owner,
        string $related,
        string $foreign_key,
        string $as,
        public readonly ?string $through = null,
    ) {
        assert(is_string($owner->primary));

        parent::__construct(
            owner: $owner,
            related: $related,
            local_key: $owner->primary,
            foreign_key: $foreign_key,
            as: $as,
        );
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
            ->resolve_related_model()
            ->where([ $this->foreign_key => $record->{$this->local_key} ]);
    }

    /**
     * @param class-string<ActiveRecord> $through
     *
     * @return Query<ActiveRecord>
     *
     * https://guides.rubyonrails.org/association_basics.html#the-has-many-through-association
     */
    private function build_through_query(ActiveRecord $record, string $through): Query
    {
        // $owner == $r1_model
        // $related === $r2_model

        $owner = $this->owner;
        $related = $this->resolve_related_model();
        $through_model = $this->model_for_activerecord($through);
        $r = $through_model->relations;
        $r1 = $r->find(fn(Relation $r) => $r->related === $this->owner->activerecord_class);
        $r2 = $r->find(fn(Relation $r) => $r->related === $related->activerecord_class)
            ?? throw new LogicException("Unable to find related model for " . $related::class);
        $r2_model = $this->model_for_activerecord($r2->related);

        $q = $related->query()->select("`{alias}`.*");
        // Because of the select, we need to set the mode otherwise an array would be
        // fetched instead of an object.
        $q->mode(PDO::FETCH_CLASS, $related->activerecord_class);
        //phpcs:disable Generic.Files.LineLength.TooLong
        $q->join(expression: "INNER JOIN `$through_model->name` ON `$through_model->name`.{$r2->local_key} = `$r2_model->alias`.{$related->primary}");
        //phpcs:disable Generic.Files.LineLength.TooLong
        $q->join(expression: "INNER JOIN `$owner->name` `$owner->alias` ON `$through_model->name`.{$r1->local_key} = `$owner->alias`.{$owner->primary}");
        $q->where("`$owner->alias`.{$owner->primary} = ?", $record->{$this->local_key});

        return $q;
    }

    protected function resolve_property_name(string $related): string
    {
        return pluralize(parent::resolve_property_name($related));
    }
}
