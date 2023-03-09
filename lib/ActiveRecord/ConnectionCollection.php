<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ArrayAccess;
use ArrayIterator;
use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use InvalidArgumentException;
use IteratorAggregate;
use PDOException;
use Traversable;

use function get_debug_type;

/**
 * Connection collection.
 *
 * @property-read array<string, ConnectionDefinition> $definitions Connection definitions.
 * @property-read Connection[] $established Established connections.
 *
 * @implements ArrayAccess<string, Connection>
 * @implements IteratorAggregate<string, Connection>
 */
class ConnectionCollection implements ArrayAccess, IteratorAggregate, ConnectionProvider
{
    /**
     * @uses get_definitions
     * @uses get_established
     */
    use AccessorTrait;

    /**
     * Connections definitions.
     *
     * @var array<string, ConnectionDefinition>
     */
    private array $attributes;

    /**
     * @return array<string, array>
     */
    private function get_definitions(): array
    {
        return $this->attributes;
    }

    /**
     * Established connections.
     *
     * @var array<string, Connection>
     *     Where _key_ is a connection identifier.
     */
    private array $established = [];

    /**
     * @return array<string, Connection>
     */
    private function get_established(): array
    {
        return $this->established;
    }

    /**
     * @param array<string, ConnectionDefinition> $attributes_by_id
     *     Where _key_ is a connection identifier.
     */
    public function __construct(array $attributes_by_id)
    {
        foreach ($attributes_by_id as $id => $definition) {
            $this[$id] = $definition;
        }
    }

    public function connection_for_id(string $id): Connection
    {
        return $this[$id];
    }

    /**
     * Checks if a connection definition exists.
     *
     * @param string $offset Connection identifier.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Sets the definition of a connection.
     *
     * @param string $offset Connection identifier.
     * @param mixed $value Connection definition.
     *
     * @throws ConnectionAlreadyEstablished in attempt to set the definition of an already
     * established connection.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (isset($this->established[$offset])) {
            throw new ConnectionAlreadyEstablished($offset);
        }

        if (!$value instanceof ConnectionDefinition) {
            $expected = ConnectionDefinition::class;
            $actual = get_debug_type($value);
            throw new InvalidArgumentException("Expected '$expected' got '$actual'");
        }

        $this->attributes[$offset] = $value;
    }

    /**
     * Removes a connection definition.
     *
     * @param string $offset Connection identifier.
     *
     * @throws ConnectionAlreadyEstablished in attempt to unset the definition of an already
     * established connection.
     */
    public function offsetUnset(mixed $offset): void
    {
        if (isset($this->established[$offset])) {
            throw new ConnectionAlreadyEstablished($offset);
        }

        unset($this->attributes[$offset]);
    }

    /**
     * Returns a connection to the specified database.
     *
     * If the connection has not been established yet, it is created on the fly.
     *
     * @param string $offset Connection identifier.
     *
     * @return Connection
     *
     * @throws ConnectionNotDefined when the connection requested is not defined.
     * @throws ConnectionNotEstablished when the connection failed.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->established[$offset] ??=
            $this->make_connection($offset);
    }

    private function make_connection(string $id): Connection
    {
        if (!$this->offsetExists($id)) {
            throw new ConnectionNotDefined($id);
        }

        #
        # we catch connection exceptions and rethrow them in order to avoid displaying sensible
        # information such as the username or password.
        #

        try {
            return $this->established[$id] = new Connection($this->attributes[$id]);
        } catch (PDOException $e) {
            throw new ConnectionNotEstablished(
                $id,
                "Connection not established: {$e->getMessage()}."
            );
        }
    }

    /**
     * Returns an iterator for established connections.
     *
     * @return Traversable<string, Connection>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->established);
    }
}
