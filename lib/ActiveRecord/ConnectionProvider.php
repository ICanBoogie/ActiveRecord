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
