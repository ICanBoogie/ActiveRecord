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
use IteratorAggregate;
use PDOException;
use Traversable;

/**
 * Connection collection.
 *
 * @property-read array $definitions Connection definitions.
 * @property-read Connection[] $established Established connections.
 *
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
     * @var array<string, array>
     */
    private array $definitions;

    /**
     * @return array<string, array>
     */
    private function get_definitions(): array
    {
        return $this->definitions;
    }

    /**
     * Established connections.
     *
     * @var Connection[]
     */
    private array $established = [];

    /**
     * @return Connection[]
     */
    private function get_established(): array
    {
        return $this->established;
    }

    /**
     * Initializes the {@link $definitions} property.
     *
     * @param array<string, array> $definitions Connection definitions.
     */
    public function __construct(array $definitions)
    {
        foreach ($definitions as $id => $definition) {
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
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->definitions[$offset]);
    }

    /**
     * Sets the definition of a connection.
     *
     * @param string $offset Connection identifier.
     * @param array|string $value Connection definition.
     *
     * @throws ConnectionAlreadyEstablished in attempt to set the definition of an already
     * established connection.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (isset($this->established[$offset])) {
            throw new ConnectionAlreadyEstablished($offset);
        }

        if (is_string($value)) {
            $value = [ 'dsn' => $value ];
        }

        if (empty($value['dsn'])) {
            throw new \InvalidArgumentException("<q>dsn</q> is empty or not defined.");
        }

        $this->definitions[$offset] = $value;
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

        unset($this->definitions[$offset]);
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
        if (isset($this->established[$offset])) {
            return $this->established[$offset];
        }

        if (!$this->offsetExists($offset)) {
            throw new ConnectionNotDefined($offset);
        }

        $options = $this->definitions[$offset] + [

                'dsn' => null,
                'username' => 'root',
                'password' => null

            ];

        $options['options'][ConnectionOptions::ID] = $offset;

        #
        # we catch connection exceptions and rethrow them in order to avoid displaying sensible
        # information such as the username or password.
        #

        try {
            return $this->established[$offset] = new Connection(
                $options['dsn'],
                $options['username'],
                $options['password'],
                $options['options']
            );
        } catch (PDOException $e) {
            throw new ConnectionNotEstablished(
                $offset,
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
