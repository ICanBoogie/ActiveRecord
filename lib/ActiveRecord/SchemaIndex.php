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
 * @deprecated
 */
final class SchemaIndex
{
    /**
     * @param array{
     *     columns: array<string>,
     *     unique: bool,
     *     name: ?string
     * } $an_array
     *
     * @return object
     */
    public static function __set_state(array $an_array): object
    {
        return new self(...$an_array);
    }

    /**
     * @param string[] $columns
     */
    public function __construct(
        public array $columns,
        public bool $unique = false,
        public ?string $name = null,
    ) {
    }
}
