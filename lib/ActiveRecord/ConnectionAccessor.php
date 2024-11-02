<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;

/**
 * @see ConnectionIterator::connection_iterator()
 */
final readonly class ConnectionAccessor
{
    public function __construct(
        public ConnectionDefinition $definition,
        public bool $instantiated,
        private ConnectionProvider $provider,
    ) {
    }

    /**
     * Returns the {@see Connection} matching the {@see ConnectionDefinition} from the {@see ConnectionProvider}.
     */
    public function get(): Connection
    {
        return $this->provider->connection_for_id($this->definition->id);
    }
}
