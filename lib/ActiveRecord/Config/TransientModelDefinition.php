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
     */
    public function __construct( // @phpstan-ignore-line
        public readonly string $id,
        public Schema $schema,
        public readonly string $model_class,
        public readonly ?string $name = null,
        public readonly ?string $alias = null,
        public readonly ?string $implements = null,
        public readonly string $connection = Config::DEFAULT_CONNECTION_ID,
    ) {
    }
}
