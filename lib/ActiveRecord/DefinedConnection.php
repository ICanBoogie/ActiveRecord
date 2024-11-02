<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;

/**
 * @internal
 * @see ConnectionIterator
 */
final readonly class DefinedConnection
{
    public function __construct(
        public ConnectionDefinition $definition,
        public bool $established,
        private ConnectionProvider $provider,
    ) {
    }

    public function connect(): Connection
    {
        return $this->provider->connection_for_id($this->definition->id);
    }
}
