<?php

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
