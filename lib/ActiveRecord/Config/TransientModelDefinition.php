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
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<Model>|null $model_class
     * @param class-string<Query>|null $query_class
     */
    public function __construct( // @phpstan-ignore-line
        public readonly string $id,
        public Schema $schema,
        public readonly string $activerecord_class,
        public readonly string $connection = Config::DEFAULT_CONNECTION_ID,
        public readonly ?string $name = null,
        public readonly ?string $alias = null,
        public readonly ?string $extends = null,
        public readonly ?string $implements = null,
        public readonly ?string $model_class = null,
        public readonly ?string $query_class = null,
    ) {
    }
}
