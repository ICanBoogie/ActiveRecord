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
 * @property-read ConnectionCollection $connections
 * @property-read array $definitions
 * @property-read Model[] $instances
 */
class ModelCollection implements \ArrayAccess
{
	use AccessorTrait;

	/**
	 * Instantiated models.
	 *
	 * @var Model[]
	 */
	protected $instances = [];

	protected function get_instances()
	{
		return $this->instances;
	}

	/**
	 * Models definitions.
	 *
	 * @var array
	 */
	protected $definitions = [];

	protected function get_definitions()
	{
		return $this->definitions;
	}

	/**
	 * @var ConnectionCollection
	 */
	protected $connections;

	protected function get_connections()
	{
		return $this->connections;
	}

	/**
	 * Initializes the {@link $connections} and {@link $definitions} properties.
	 *
	 * @param ConnectionCollection $connections
	 * @param array $definitions Model definitions.
	 */
	public function __construct(ConnectionCollection $connections, array $definitions = [])
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

		return $this->instances[$id] = $this
			->instantiate_model($this
				->resolve_model_attributes($this->definitions[$id]));
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
	 * Resolves model attributes.
	 *
	 * The methods replaces {@link Model::CONNECTION} and {@link Model::EXTENDING} identifier
	 * with instances.
	 *
	 * @param array $attributes
	 *
	 * @return array
	 */
	protected function resolve_model_attributes(array $attributes)
	{
		$attributes += [

			Model::CLASSNAME => Model::class,
			Model::CONNECTION => 'primary',
			Model::EXTENDING => null

		];

		$connection = &$attributes[Model::CONNECTION];

		if ($connection && !($connection instanceof Connection))
		{
			$connection = $this->connections[$connection];
		}

		$extending = &$attributes[Model::EXTENDING];

		if ($extending && !($extending instanceof Model))
		{
			$extending = $this[$extending];
		}

		return $attributes;
	}

	/**
	 * Instantiate a model with the specified attributes.
	 *
	 * @param array $attributes
	 *
	 * @return Model
	 */
	protected function instantiate_model(array $attributes)
	{
		$class = $attributes[Model::CLASSNAME];

		return new $class($this, $attributes);
	}

	/**
	 * Install all the models.
	 *
	 * @return ModelCollection
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
	 * @return ModelCollection
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
	 * @return array An array of key/value pair where _key_ is a model identifier and
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
