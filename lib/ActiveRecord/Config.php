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

final class Config
{
    public const DEFAULT_CONNECTION_ID = 'primary';

    /**
     * @param array{
     *     connections: array<string, array<ActiveRecord\ConnectionOptions::*, mixed>>,
     *     models: array<string, array<Model::*, mixed>>,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(...$an_array);
    }

    /**
     * @param array<string, array<ActiveRecord\ConnectionOptions::*, mixed>> $connections
     * @param array<string, array<Model::*, mixed>> $models
     */
    public function __construct(
        public readonly array $connections,
        public readonly array $models,
    ) {
    }
}
