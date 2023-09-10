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
     */
    public function __construct( // @phpstan-ignore-line
        public Schema $schema,
        public readonly string $model_class,
        public readonly string $activerecord_class,
        public readonly string $table_name,
        public readonly string $alias,
        public readonly string $connection = Config::DEFAULT_CONNECTION_ID,
    ) {
        Assert::extends_model($this->model_class);
    }
}
