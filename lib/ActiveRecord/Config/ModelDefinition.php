<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

/**
 * @internal
 *
 * A Model definition, built during configuration.
 */
readonly class ModelDefinition
{
    /**
     * @param array{
     *     table: TableDefinition,
     *     model_class: class-string<Model>,
     *     activerecord_class: class-string<ActiveRecord>,
     *     query_class: class-string<Query>,
     *     connection: non-empty-string,
     *     association: ?Association
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(... $an_array);
    }

    /**
     * @param class-string<Model> $model_class
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<Query> $query_class
     * @param non-empty-string $connection
     */
    public function __construct(
        public TableDefinition $table,
        public string $model_class,
        public string $activerecord_class,
        public string $query_class,
        public string $connection,
        public ?Association $association = null,
    ) {
    }
}
