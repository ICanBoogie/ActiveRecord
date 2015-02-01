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
 * Model collection.
 *
 * @property-read Connections $connections
 * @property-read array[string]array $definitions
 * @property-read array[string]Model $instances
 */
class Models implements \ArrayAccess
{
	use AccessorTrait;

	/**
	 * Instantiated models.
	 *
	 * @var array[string]Model
	 */
	protected $instances = [];

	protected function get_instances()
	{
		return $this->instances;
	}

	/**
	 * Models definitions.
	 *
	 * @var array[string]array
	 */
	protected $definitions = [];

	protected function get_definitions()
	{
		return $this->definitions;
	}

	/**
	 * Connections manager.
	 *
	 * @var Connections
	 */
	protected $connections;

	protected function get_connections()
	{
		return $this->connections;
	}

	/**
	 * Initializes the {@link $connections} and {@link $definitions} properties.
	 *
	 * @param Connections $connections Connections manager.
	 * @param array[string]array $definitions Model definitions.
	 */
	public function __construct(Connections $connections, array $definitions=[])
	{
		$this->connections = $connections;

		foreach ($definitions as $id => $definition)
		{
			$this[$id] = $definition;
		}
	}

	/**
	 * Checks if a model is defined.
	 *
	 * @param string $id Model identifier.
	 *
	 * @return bool
	 */
	public function offsetExists($id)
	{
		return isset($this->definitions[$id]);
	}

	/**
	 * Sets the definition of a model.
	 *
	 * The {@link Model::ID} and {@link Model::NAME} are set to the provided id if they are not
	 * defined.
	 *
	 * @param string $id Model identifier.
	 * @param array $definition Model definition.
	 *
	 * @throws ModelAlreadyInstantiated in attempt to write a model already instantiated.
	 */
	public function offsetSet($id, $definition)
	{
		if (isset($this->instances[$id]))
		{
			throw new ModelAlreadyInstantiated($id);
		}

		$this->definitions[$id] = $definition + [

			Model::ID => $id,
			Model::NAME => $id

		];
	}

	/**
	 * Returns a {@link Model} instance.
	 *
	 * @param string $id Model identifier.
	 *
	 * @return Model
	 *
	 * @throws ModelNotDefined when the model is not defined.
	 */
	public function offsetGet($id)
	{
		if (isset($this->instances[$id]))
		{
			return $this->instances[$id];
		}

		if (!isset($this->definitions[$id]))
		{
			throw new ModelNotDefined($id);
		}

		$properties = $this->definitions[$id] + [

			Model::CONNECTION => 'primary',
			Model::CLASSNAME => __NAMESPACE__ . '\Model'

		];

		if (is_string($properties[Model::CONNECTION]))
		{
			$properties[Model::CONNECTION] = $this->connections[$properties[Model::CONNECTION]];
		}

		$class = $properties[Model::CLASSNAME];

		return new $class($properties);
	}

	/**
	 * Unset the definition of a model.
	 *
	 * @param string $id Model identifier.
	 *
	 * @throws ModelAlreadyInstantiated in attempt to unset the definition of an already
	 * instantiated model.
	 */
	public function offsetUnset($id)
	{
		if (isset($this->instances[$id]))
		{
			throw new ModelAlreadyInstantiated($id);
		}

		unset($this->definitions[$id]);
	}

	/**
	 * Install all the models.
	 *
	 * @return Models
	 */
	public function install()
	{
		foreach (array_keys($this->definitions) as $id)
		{
			$model = $this[$id];

			if ($model->is_installed())
			{
				continue;
			}

			$model->install();
		}

		return $this;
	}

	/**
	 * Uninstall all the models.
	 *
	 * @return Models
	 */
	public function uninstall()
	{
		foreach (array_keys($this->definitions) as $id)
		{
			$model = $this[$id];

			if (!$model->is_installed())
			{
				continue;

			}

			$model->uninstall();
		}

		return $this;
	}

	/**
	 * Check if models are installed.
	 *
	 * @return array[string]bool An array of key/value pair where _key_ is a model identifier and
	 * _value_ `true` if the model is installed, `false` otherwise.
	 */
	public function is_installed()
	{
		$rc = [];

		foreach (array_keys($this->definitions) as $id)
		{
			$rc[$id] = $this[$id]->is_installed();
		}

		return $rc;
	}
}
