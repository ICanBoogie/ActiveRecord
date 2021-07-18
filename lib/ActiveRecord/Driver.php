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

use Throwable;

/**
 * Connection driver interface.
 */
interface Driver
{
    /**
     * Quotes a string, or an array of strings.
     */
    public function quote_string(string $string): string;

    /**
     * Quotes an identifier, or an array of identifiers.
     */
    public function quote_identifier(string $identifier): string;

    /**
     * Casts a value into a database compatible representation.
     *
     * @param string|null $type One of `SchemaColumn::TYPE_*`.
     */
    public function cast_value(mixed $value, string $type = null): mixed;

    /**
     * Creates a table given a schema.
     *
     * @throws Throwable
     */
    public function create_table(string $table_name, Schema $schema): void;

    /**
     * Creates indexes given a schema.
     *
     * @throws Throwable
     */
    public function create_indexes(string $table_name, Schema $schema): void;

    /**
     * Checks if a specified table exists in the database.
     *
     * @param string $name The unprefixed name of the table.
     */
    public function table_exists(string $name): bool;

    /**
     * Optimizes the tables of the database.
     */
    public function optimize(): void;
}
