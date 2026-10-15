<?php

namespace ICanBoogie\ActiveRecord\Schema;

use Attribute;
use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

/**
 * Configures a record discovered by {@see \ICanBoogie\ActiveRecord\ConfigBuilder::enable_discovery()}.
 *
 * It provides the options of {@see \ICanBoogie\ActiveRecord\ConfigBuilder::add_record()} that cannot be
 * inferred from the schema attributes. The attribute is not inherited, a child record must declare its own.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Record
{
    /**
     * @param class-string<Model<*>> $model_class
     * @param class-string<Query<*>> $query_class
     * @param non-empty-string|null $table_name
     * @param non-empty-string|null $alias
     * @param non-empty-string $connection
     */
    public function __construct(
        public string $model_class = Model::class,
        public string $query_class = Query::class,
        public ?string $table_name = null,
        public ?string $alias = null,
        public string $connection = Config::DEFAULT_CONNECTION_ID,
    ) {
    }
}
