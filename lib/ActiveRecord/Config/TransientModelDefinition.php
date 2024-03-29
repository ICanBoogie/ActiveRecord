<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\Schema;

/**
 * Model configuration, used during configuration.
 *
 * @internal
 */
final class TransientModelDefinition
{
    /**
     * @param class-string<Model> $model_class
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<Query> $query_class
     * @param non-empty-string $table_name
     * @param non-empty-string $alias
     * @param non-empty-string $connection
     */
    public function __construct(
        public Schema $schema,
        public readonly string $model_class,
        public readonly string $activerecord_class,
        public readonly string $query_class,
        public readonly string $table_name,
        public readonly string $alias,
        public readonly string $connection = Config::DEFAULT_CONNECTION_ID,
    ) {
    }
}
