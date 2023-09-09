<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use LogicException;

use Test\ICanBoogie\Acme\ArticleModel;

use function get_parent_class;
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

    /**
     * @param class-string<Model> $model_class
     */
    public static function model_activerecord(string $model_class): void
    {
        $activerecord_class = ActiveRecord::class;
        /** @var class-string<Model> $parent_class */
        $parent_class = get_parent_class($model_class);

        if ($parent_class !== Model::class) {
            $activerecord_class = $parent_class::activerecord_class;
        }

        is_subclass_of($model_class::activerecord_class, $activerecord_class)
            or throw new LogicException("$model_class needs to override the constant activerecord_class with a class that extends $activerecord_class");
    }
}
