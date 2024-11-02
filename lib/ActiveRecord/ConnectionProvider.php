<?php

namespace ICanBoogie\ActiveRecord;

/**
 * Provides connections.
 */
interface ConnectionProvider
{
    /**
     * Provides a connection for a given identifier.
     *
     * @param non-empty-string $id
     *     A connection identifier.
     *
     * @throws ConnectionNotDefined
     * @throws ConnectionNotEstablished
     */
    public function connection_for_id(string $id): Connection;
}
