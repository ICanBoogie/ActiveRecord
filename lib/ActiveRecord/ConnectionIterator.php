<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;

/**
 * Iterables over defined connections.
 */
interface ConnectionIterator
{
    /**
     * Returns an iterator of defined connections.
     *
     * @return iterable<non-empty-string, DefinedConnection>
     *     Where _key_ is a connection identifier.
     */
    public function connection_iterator(): iterable;
}

/**
 * @internal
 */
final class DefinedConnection {
    public function __construct(
        public readonly ConnectionDefinition $definition,
        public readonly bool $established,
        private readonly ConnectionProvider $provider,
    ) {
    }

    public function connect(): Connection
    {
        return $this->provider->connection_for_id($this->definition->id);
    }
}
