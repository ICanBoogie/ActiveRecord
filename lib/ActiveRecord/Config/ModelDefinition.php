<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\TableDefinition;

use function ICanBoogie\pluralize;
use function ICanBoogie\underscore;
use function str_ends_with;
use function strlen;
use function strrpos;
use function substr;

/**
 * @internal
 *
 * A Model definition, built during configuration.
 */
final class ModelDefinition
{
    /**
     * @param array{
     *     table: TableDefinition,
     *     id: string,
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
        public readonly string $id,
        public readonly string $model_class,
        public readonly string $connection,
        public readonly ?Association $association = null,
    ) {
    }
}
