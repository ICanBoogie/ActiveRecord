<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\TableDefinition;

/**
 * Model configuration, used during configuration.
 */
final class ModelDefinition extends TableDefinition
{
    /**
     * @param array{
     *     id: string,
     *     connection: string,
     *     schema: Schema,
     *     model_class: class-string<Model>,
     *     name: ?string,
     *     alias: ?string,
     *     implements: ?string,
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
        public readonly string $id,
        public readonly string $connection,
        Schema $schema,
        public readonly string $model_class,
        ?string $name = null,
        ?string $alias = null,
        ?string $implements = null,
        public readonly ?Association $association = null,
    ) {
        parent::__construct(
            name: $name ?? $id,
            schema: $schema,
            alias: $alias,
            implements: $implements
        );
    }
}
