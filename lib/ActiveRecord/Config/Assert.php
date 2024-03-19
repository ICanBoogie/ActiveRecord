<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use LogicException;

use function is_subclass_of;

final class Assert
{
    public static function extends_model(string $value): void
    {
        is_subclass_of($value, Model::class)
            or throw new LogicException("'$value' is not extending " . Model::class);
    }

    public static function extends_activerecord(string $value, string $message = null): void
    {
        is_subclass_of($value, ActiveRecord::class)
            or throw new LogicException($message ?? "'$value' is not extending " . ActiveRecord::class);
    }
}
