<?php

namespace ICanBoogie\ActiveRecord\Config;

final class TransientBelongsToAssociation
{
    public function __construct(
        public readonly string $model_id,
        public readonly string|null $local_key,
        public readonly string|null $foreign_key,
        public readonly string|null $as,
    ) {
    }
}
