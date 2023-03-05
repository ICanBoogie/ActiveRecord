<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\Schema;

/**
 * Model configuration, used during configuration.
 *
 * @internal
 */
final class ModelConfig extends TableConfig
{
    public function __construct(
        public readonly string $id,
        string $connection,
        Schema $schema,
        public readonly string $activerecord_class,
        ?string $name = null,
        ?string $alias = null,
        ?string $extends = null,
        ?string $implements = null,
        public readonly string $model_class = Model::class,
        public readonly string $query_class = Query::class,
        public readonly ?Association $association = null,
    ) {
        parent::__construct(
            name: $name ?? $id,
            connection: $connection,
            schema: $schema,
            alias: $alias,
            extends: $extends,
            implements: $implements
        );
    }

    /**
     * @return array<Model::*, mixed>
     */
    public function to_array(): array
    {
        return parent::to_array() + [

            Model::ID => $this->id,
            Model::ACTIVERECORD_CLASS => $this->activerecord_class,
            Model::CLASSNAME => $this->model_class,
            Model::QUERY_CLASS => $this->query_class,

        ] + ($this->association ? $this->association->to_array() : []);
    }
}
