<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use PDOException;

use function ICanBoogie\iterable_to_dictionary;

/**
 * A collection of connections.
 */
class ConnectionCollection implements ConnectionProvider, ConnectionIterator
{
    /**
     * @var array<string, ConnectionDefinition>
     *     Where _key_ is a connection identifier.
     */
    public readonly array $definitions;

    /**
     * Established connections.
     *
     * @var array<string, Connection>
     *     Where _key_ is a connection identifier.
     */
    private array $established = [];

    /**
     * @param ConnectionDefinition[] $definitions
     */
    public function __construct(iterable $definitions)
    {
        // @phpstan-ignore-next-line
        $this->definitions = iterable_to_dictionary($definitions, fn(ConnectionDefinition $d) => $d->id);
    }

    public function connection_for_id(string $id): Connection
    {
        return $this->established[$id] ??= $this->new_connection($id);
    }

    private function new_connection(string $id): Connection
    {
        $definition = $this->definitions[$id]
            ?? throw new ConnectionNotDefined($id);

        #
        # we catch connection exceptions and rethrow them in order to avoid displaying sensible
        # information such as the username or password.
        #

        try {
            return new Connection($definition);
        } catch (PDOException $e) {
            throw new ConnectionNotEstablished(
                $id,
                "Connection not established: {$e->getMessage()}."
            );
        }
    }

    public function connection_iterator(): iterable
    {
        foreach ($this->definitions as $id => $definition) {
            yield $id => new ConnectionAccessor(
                $definition,
                isset($this->established[$id]),
                $this
            );
        }
    }
}
