<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\TableDefinition;

/**
 * @internal
 *
 * A Model definition, built during configuration.
 */
class ModelDefinition
{
    /**
     * @param array{
     *     table: TableDefinition,
     *     model_class: class-string<Model>,
     *     connection: string,
     *     association: ?Association
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(... $an_array);
    }

    /**
     * @param class-string<Model> $model_class
     */
    public function __construct(
        public readonly TableDefinition $table,
        public readonly string $model_class,
        public readonly string $connection,
        public readonly ?Association $association = null,
    ) {
    }
}
