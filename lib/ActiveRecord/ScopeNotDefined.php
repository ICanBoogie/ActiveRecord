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

/**
 * Exception thrown in attempt to obtain a scope that is not defined.
 *
 * @property-read string $scope_name
 * @property-read Model $model
 */
class ScopeNotDefined extends \LogicException implements Exception
{
	/**
	 * @uses get_scope_name
	 * @uses get_model
	 */
	use AccessorTrait;

	/**
	 * Name of the scope.
	 *
	 * @var string
	 */
	private $scope_name;

	private function get_scope_name(): string
	{
		return $this->scope_name;
	}

	/**
	 * Model on which the scope was invoked.
	 *
	 * @var Model
	 */
	private $model;

	private function get_model(): Model
	{
		return $this->model;
	}

	/**
	 * Initializes the {@link $scope_name} and {@link $model} properties.
	 *
	 * @param string $scope_name Name of the scope.
	 * @param Model $model Model on which the scope was invoked.
	 * @param int $code Default to 404.
	 * @param \Throwable $previous Previous exception.
	 */
	public function __construct(string $scope_name, Model $model, int $code = 500, \Throwable $previous = null)
	{
		$this->scope_name = $scope_name;
		$this->model = $model;

		parent::__construct($this->format_message($scope_name, $model), $code, $previous);
	}

	private function format_message(string $scope_name, Model $model): string
	{
		return "Unknown scope `{$scope_name}` for model `{$model->unprefixed_name}`.";
	}
}
