<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Schema;

/**
 * Model configuration, used during configuration.
 *
 * @internal
 */
final class TransientModelConfig
{
    public function __construct(
        public readonly string $id,
        public readonly Schema $schema,
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
