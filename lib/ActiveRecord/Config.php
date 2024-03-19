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

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;

final readonly class Config
{
    public const DEFAULT_CONNECTION_ID = 'primary';

    /**
     * @param array{
     *     connections: array<non-empty-string, ConnectionDefinition>,
     *     models: array<class-string<ActiveRecord>, ModelDefinition>,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param array<non-empty-string, ConnectionDefinition> $connections
     *     Where _key_ is an identifier.
     * @param array<class-string<ActiveRecord>, ModelDefinition> $models
     */
    public function __construct(
        public array $connections,
        public array $models,
    ) {
    }
}
