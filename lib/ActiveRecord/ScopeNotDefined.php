<?php

namespace ICanBoogie\ActiveRecord;

use LogicException;
use Throwable;

/**
 * Exception thrown in attempt to obtain a scope that is not defined.
 */
class ScopeNotDefined extends LogicException implements Exception
{
    public function __construct(
        public readonly string $scope_name,
        public readonly Model $model,
        Throwable $previous = null
    ) {
        parent::__construct($this->format_message($scope_name, $model), 0, $previous);
    }

    private function format_message(string $scope_name, Model $model): string
    {
        return "Unknown scope `$scope_name` for model `$model->unprefixed_name`.";
    }
}
