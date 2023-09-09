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
use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * Model collection.
 *
 * @property-read array<string, ModelDefinition> $definitions
 * @property-read array<class-string<Model>, Model> $instances
 */
class ModelCollection implements ModelProvider, ModelResolver, ModelIterator
{
    /**
     * @uses get_instances
     * @uses get_definitions
     */
    use AccessorTrait;

    /**
     * Instantiated models.
     *
     * @var array<class-string<Model>, Model>
     */
    private array $instances = [];

    /**
     * @return array<class-string<Model>, Model>
     */
    private function get_instances(): array
    {
        return $this->instances;
    }

    /**
     * Models definitions.
     *
     * @var array<class-string<Model>, ModelDefinition>
     */
    private array $definitions = [];

    /**
     * @var array<class-string<ActiveRecord>, class-string<Model>>
     */
    private array $activerecord_class_to_model_class = [];

    /**
     * @return array<string, ModelDefinition>
     */
    private function get_definitions(): array
    {
        return $this->definitions;
    }

    /**
     * @param ModelDefinition[] $definitions
     */
    public function __construct(
        public readonly ConnectionProvider $connections,
        iterable $definitions,
    ) {
        foreach ($definitions as $definition) {
            assert($definition instanceof ModelDefinition);

            $this->definitions[$definition->model_class] = $definition;
            // @phpstan-ignore-next-line
            $this->activerecord_class_to_model_class[$definition->activerecord_class] = $definition->model_class;
        }
    }

    public function model_iterator(): iterable
    {
        foreach ($this->definitions as $definition) {
            // @phpstan-ignore-next-line
            yield $definition->model_class => fn() => $this->model_for_class($definition->model_class);
        }
    }

    public function model_for_class(string $class): Model
    {
        // @phpstan-ignore-next-line
        return $this->instances[$class] ??= $this->instantiate_model($class);
    }

    /**
     * @param class-string<Model> $class
     */
    private function instantiate_model(string $class): Model
    {
        $definition = $this->definitions[$class]
            ?? throw new LogicException("No definition for model '$class'");

        return new $class(
            $this->connections->connection_for_id($definition->connection),
            $this,
            $definition
        );
    }

    public function model_for_activerecord(string|ActiveRecord $class_or_activerecord): Model
    {
        $class = $class_or_activerecord instanceof ActiveRecord
            ? $class_or_activerecord::class
            : $class_or_activerecord;

        $model_class = $this->activerecord_class_to_model_class[$class]
            ?? throw new RuntimeException("No model defined for activerecord class '$class'");

        return $this->model_for_class($model_class);
    }

    /**
     * Install all the models.
     *
     * @throws Throwable
     */
    public function install(): void
    {
        foreach ($this->model_iterator() as $get) {
            $model = $get();

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
        foreach ($this->model_iterator() as $get) {
            $model = $get();

            if (!$model->is_installed()) {
                continue;
            }

            $model->uninstall();
        }
    }

    /**
     * Check if models are installed.
     *
     * @return array<class-string<Model>, bool>
     *     An array of key/value pair where _key_ is a model class and
     *     _value_ `true` if the model is installed, `false` otherwise.
     */
    public function is_installed(): array
    {
        $rc = [];

        foreach ($this->model_iterator() as $class => $get) {
            $rc[$class] = $get()->is_installed();
        }

        return $rc;
    }
}
