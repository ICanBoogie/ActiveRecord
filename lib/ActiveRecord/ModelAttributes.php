<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\Association;

/**
 * Model configuration, used during configuration.
 */
final class ModelAttributes extends TableAttributes
{
    /**
     * @param array{
     *     id: string,
     *     connection: string,
     *     schema: Schema,
     *     activerecord_class: class-string<ActiveRecord>,
     *     name: ?string,
     *     alias: ?string,
     *     extends: ?string,
     *     implements: ?string,
     *     model_class: class-string<Model<int|string|string[], ActiveRecord>>,
     *     query_class: class-string<Query<ActiveRecord>>,
     *     association: ?Association
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(... $an_array);
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<Model<int|string|string[], ActiveRecord>> $model_class
     * @param class-string<Query<ActiveRecord>> $query_class
     */
    public function __construct(
        public readonly string $id,
        public readonly string $connection,
        Schema $schema,
        public readonly string $activerecord_class,
        ?string $name = null,
        ?string $alias = null,
        ?string $extends = null,
        ?string $implements = null,
        public readonly string $model_class = Model::class,
        public readonly string $query_class = Query::class,
        public readonly ?Association $association = null,
    ) {
        parent::__construct(
            name: $name ?? $id,
            schema: $schema,
            alias: $alias,
            extends: $extends,
            implements: $implements
        );
    }
}
