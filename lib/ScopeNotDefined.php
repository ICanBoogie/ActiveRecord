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
 * Exception thrown in attempt to obtain a scope that is not defined.
 *
 * @property-read string $scope_name
 * @property-read Model $model
 */
class ScopeNotDefined extends \LogicException implements Exception
{
	use \ICanBoogie\GetterTrait;

	/**
	 * Name of the scope.
	 *
	 * @var string
	 */
	private $scope_name;

	protected function get_scope_name()
	{
		return $this->scope_name;
	}

	/**
	 * Model on which the scope was invoked.
	 *
	 * @var Model
	 */
	private $model;

	protected function get_model()
	{
		return $this->model;
	}

	/**
	 * Initializes the {@link $scope_name} and {@link $model} properties.
	 *
	 * @param string $scope_name Name of the scope.
	 * @param Model $model Model on which the scope was invoked.
	 * @param int $code Default to 404.
	 * @param \Exception $previous Previous exception.
	 */
	public function __construct($scope_name, Model $model, $code=500, \Exception $previous=null)
	{
		$this->scope_name = $scope_name;
		$this->model = $model;

		parent::__construct("Unknown scope `{$scope_name}` for model `{$model->name_unprefixed}`.", $code, $previous);
	}
}
