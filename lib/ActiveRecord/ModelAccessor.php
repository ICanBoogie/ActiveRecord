<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ModelDefinition;

/**
 * @see ConnectionIterator::connection_iterator()
 */
final readonly class ModelAccessor
{
    public function __construct(
        public ModelDefinition $definition,
        public bool $instantiated,
        private ModelProvider $provider,
    ) {
    }

    /**
     * Returns the {@see Model} matching the {@see ModelDefinition} from the {@see ModelProvider}.
     */
    public function get(): Model
    {
        return $this->provider->model_for_record($this->definition->activerecord_class);
    }
}
