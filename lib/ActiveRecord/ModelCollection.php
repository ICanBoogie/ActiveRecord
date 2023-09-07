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

use ArrayAccess;
use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;
use LogicException;
use RuntimeException;
use Throwable;

use function array_keys;
use function get_debug_type;

/**
 * Model collection.
 *
 * @property-read array<string, array> $definitions
 * @property-read array<string, Model> $instances
 *
 * @implements ArrayAccess<string, Model>
 */
class ModelCollection implements ArrayAccess, ModelProvider, ModelResolver, ModelIterator
{
    /**
     * @uses get_instances
     * @uses get_definitions
     * @uses get_connections
     */
    use AccessorTrait;

    /**
     * Instantiated models.
     *
     * @var array<string, Model>
     */
    private array $instances = [];

    /**
     * @return array<string, Model>
     */
    private function get_instances(): array
    {
        return $this->instances;
    }

    /**
     * Models definitions.
     *
     * @var array<string, ModelDefinition>
     */
    private array $definitions = [];

    /**
     * @return array<string, ModelDefinition>
     */
    private function get_definitions(): array
    {
        return $this->definitions;
    }

    /**
     * @param array<string, ModelDefinition> $definitions
     *     Where _key_ is a model identifier.
     */
    public function __construct(
        public readonly ConnectionProvider $connections,
        array $definitions = []
    ) {
        foreach ($definitions as $id => $definition) {
            $this[$id] = $definition;
        }
    }

    public function model_iterator(): iterable
    {
        foreach (array_keys($this->definitions) as $id) {
            yield $id => fn() => $this->model_for_id($id);
        }
    }

    /**
     * @return Model<int|string, ActiveRecord>
     */
    public function model_for_id(string $id): Model
    {
        return $this->offsetGet($id);
    }

    public function model_for_activerecord(string|ActiveRecord $class_or_activerecord): Model
    {
        $class = $class_or_activerecord instanceof ActiveRecord
            ? $class_or_activerecord::class
            : $class_or_activerecord;

        foreach ($this->definitions as $id => $definition) {
            if ($class === $definition->activerecord_class) {
                return $this->model_for_id($id);
            }
        }

        throw new RuntimeException("Unable to find model for $class");
    }

    /**
     * Checks if a model is defined.
     *
     * @param string $offset A Model identifier.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->definitions[$offset]);
    }

    /**
     * Sets the definition of a model.
     *
     * @param string $offset A Model identifier.
     * @param array<string, mixed>|mixed $value A Model definition.
     *
     * @throws ModelAlreadyInstantiated in attempt to write a model already instantiated.
     */
    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof ModelDefinition) {
            throw new LogicException("Expected ModelConfig instance, given: " . get_debug_type($value));
        }

        if (isset($this->instances[$offset])) {
            throw new ModelAlreadyInstantiated($offset);
        }

        $this->definitions[$offset] = $value;
    }

    /**
     * Returns a {@link Model} instance.
     *
     * @param string $offset A Model identifier.
     *
     * @throws ModelNotDefined when the model is not defined.
     */
    public function offsetGet($offset): Model
    {
        if (isset($this->instances[$offset])) {
            return $this->instances[$offset];
        }

        if (!isset($this->definitions[$offset])) {
            throw new ModelNotDefined($offset);
        }

        return $this->instances[$offset] = $this
            ->instantiate_model($this->definitions[$offset]);
    }

    /**
     * Unset the definition of a model.
     *
     * @param string $offset Model identifier.
     *
     * @throws ModelAlreadyInstantiated in attempt to unset the definition of an already
     * instantiated model.
     */
    public function offsetUnset($offset): void
    {
        if (isset($this->instances[$offset])) {
            throw new ModelAlreadyInstantiated($offset);
        }

        unset($this->definitions[$offset]);
    }

    /**
     * Install all the models.
     *
     * @throws Throwable
     */
    public function install(): void
    {
        foreach (array_keys($this->definitions) as $id) {
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
     * @throws Throwable
     */
    public function uninstall(): void
    {
        foreach (array_keys($this->definitions) as $id) {
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
     * @return array<string, bool> An array of key/value pair where _key_ is a model identifier and
     * _value_ `true` if the model is installed, `false` otherwise.
     */
    public function is_installed(): array
    {
        $rc = [];

        foreach (array_keys($this->definitions) as $id) {
            $rc[$id] = $this[$id]->is_installed();
        }

        return $rc;
    }

    /**
     * Instantiate a model with the specified attributes.
     */
    private function instantiate_model(ModelDefinition $attributes): Model
    {
        $class = $attributes->model_class;

        return new $class(
            $this->connections->connection_for_id($attributes->connection),
            $this,
            $attributes
        );
    }
}
