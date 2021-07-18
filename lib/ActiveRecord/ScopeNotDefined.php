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

use ICanBoogie\Accessor\AccessorTrait;
use LogicException;
use Throwable;

/**
 * Exception thrown in attempt to obtain a scope that is not defined.
 *
 * @property-read string $scope_name
 * @property-read Model $model
 */
class ScopeNotDefined extends LogicException implements Exception
{
    /**
     * @uses get_scope_name
     * @uses get_model
     */
    use AccessorTrait;

    private function get_scope_name(): string
    {
        return $this->scope_name;
    }

    private function get_model(): Model
    {
        return $this->model;
    }

    public function __construct(
        private string $scope_name,
        private Model $model,
        Throwable $previous = null
    ) {
        parent::__construct($this->format_message($scope_name, $model), 0, $previous);
    }

    private function format_message(string $scope_name, Model $model): string
    {
        return "Unknown scope `$scope_name` for model `$model->unprefixed_name`.";
    }
}
