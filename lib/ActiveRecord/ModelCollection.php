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
use Throwable;

/**
 * Model collection.
 *
 * @property-read array<class-string<ActiveRecord>, Model> $instances
 */
class ModelCollection implements ModelProvider, ModelIterator
{
    /**
     * @uses get_instances
     */
    use AccessorTrait;

    /**
     * Instantiated models.
     *
     * @var array<class-string<ActiveRecord>, Model>
     */
    private array $instances = [];

    /**
     * @return array<class-string<ActiveRecord>, Model>
     */
    private function get_instances(): array
    {
        return $this->instances;
    }

    /**
     * @param array<class-string<ActiveRecord>, ModelDefinition> $definitions
     */
    public function __construct(
        public readonly ConnectionProvider $connections,
        public readonly array $definitions,
    ) {
        foreach ($definitions as $activerecord_class => $definition) {
            ActiveRecord\Config\Assert::extends_activerecord($activerecord_class);
            ActiveRecord\Config\Assert::extends_activerecord($definition->activerecord_class);
        }
    }

    public function model_for_record(string $activerecord_class): Model
    {
        return $this->instances[$activerecord_class] ??= $this->instantiate_model($activerecord_class);
    }

    public function model_iterator(): iterable
    {
        foreach ($this->definitions as $activerecord_class => $definition) {
            // @phpstan-ignore-next-line
            yield $activerecord_class => fn() => $this->model_for_record($definition->activerecord_class);
        }
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     */
    private function instantiate_model(string $activerecord_class): Model
    {
        $definition = $this->definitions[$activerecord_class]
            ?? throw new LogicException("No model definition for '$activerecord_class'");

        return new $definition->model_class(
            $this->connections->connection_for_id($definition->connection),
            $this,
            $definition
        );
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

        foreach ($this->model_iterator() as $activerecord_class => $get) {
            $rc[$activerecord_class] = $get()->is_installed();
        }

        return $rc;
    }
}
