<?php

namespace ICanBoogie\ActiveRecord;

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
