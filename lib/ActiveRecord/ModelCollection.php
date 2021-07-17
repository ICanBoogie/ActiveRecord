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
     * @uses get_instances
     */
    private $instances = [];

    private function get_instances(): array
    {
        return $this->instances;
    }

    /**
     * Models definitions.
     *
     * @var array
     * @uses get_definitions
     */
    private $definitions = [];

    private function get_definitions(): array
    {
        return $this->definitions;
    }

    /**
     * @var ConnectionCollection
     * @uses get_connections
     */
    private $connections;

    private function get_connections(): ConnectionCollection
    {
        return $this->connections;
    }

    public function __construct(ConnectionCollection $connections, array $definitions = [])
    {
        $this->connections = $connections;

        foreach ($definitions as $id => $definition) {
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
        if (isset($this->instances[$id])) {
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
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->definitions[$id])) {
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
        if (isset($this->instances[$id])) {
            throw new ModelAlreadyInstantiated($id);
        }

        unset($this->definitions[$id]);
    }

    /**
     * Install all the models.
     *
     * @throws \Throwable
     */
    public function install(): void
    {
        foreach (\array_keys($this->definitions) as $id) {
            $model = $this[$id];

            if ($model->is_installed()) {
                continue;
            }

            $model->install();
        }
    }

    /**
     * Uninstall all the models.
     *
     * @throws \Throwable
     */
    public function uninstall(): void
    {
        foreach (\array_keys($this->definitions) as $id) {
            $model = $this[$id];

            if (!$model->is_installed()) {
                continue;
            }

            $model->uninstall();
        }
    }

    /**
     * Check if models are installed.
     *
     * @return array An array of key/value pair where _key_ is a model identifier and
     * _value_ `true` if the model is installed, `false` otherwise.
     */
    public function is_installed(): array
    {
        $rc = [];

        foreach (\array_keys($this->definitions) as $id) {
            $rc[$id] = $this[$id]->is_installed();
        }

        return $rc;
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
    private function resolve_model_attributes(array $attributes): array
    {
        $attributes += [

            Model::CLASSNAME => Model::class,
            Model::CONNECTION => 'primary',
            Model::EXTENDING => null

        ];

        $connection = &$attributes[Model::CONNECTION];

        if ($connection && !$connection instanceof Connection) {
            $connection = $this->connections[$connection];
        }

        $extending = &$attributes[Model::EXTENDING];

        if ($extending && !$extending instanceof Model) {
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
    private function instantiate_model(array $attributes): Model
    {
        $class = $attributes[Model::CLASSNAME];

        return new $class($this, $attributes);
    }
}
