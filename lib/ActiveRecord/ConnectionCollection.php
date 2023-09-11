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

use ArrayIterator;
use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use IteratorAggregate;
use PDOException;
use Traversable;

use function ICanBoogie\iterable_to_dictionary;

/**
 * Connection collection.
 *
 * @property-read Connection[] $established Established connections.
 *
 * @implements IteratorAggregate<string, Connection>
 */
class ConnectionCollection implements IteratorAggregate, ConnectionProvider
{
    /**
     * @uses get_definitions
     * @uses get_established
     */
    use AccessorTrait;

    /**
     * @var array<non-empty-string, ConnectionDefinition>
     *     Where _key_ is a connection identifier.
     */
    public readonly array $definitions;

    /**
     * Established connections.
     *
     * @var array<non-empty-string, Connection>
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
     * @param ConnectionDefinition[] $definitions
     */
    public function __construct(iterable $definitions)
    {
        $this->definitions = iterable_to_dictionary($definitions, fn (ConnectionDefinition $d) => $d->id);
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
