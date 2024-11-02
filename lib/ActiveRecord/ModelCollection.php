<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;
use LogicException;
use Throwable;

/**
 * Model collection.
 */
final class ModelCollection implements ModelProvider, ModelIterator
{
    /**
     * Instantiated models.
     *
     * @var array<class-string<ActiveRecord>, Model>
     */
    private array $instances = [];

    /**
     * @param array<class-string<ActiveRecord>, ModelDefinition> $definitions
     */
    public function __construct(
        public readonly ConnectionProvider $connections,
        public readonly array $definitions,
    ) {
    }

    public function model_for_record(string $activerecord_class): Model
    {
        return $this->instances[$activerecord_class] ??= $this->instantiate_model($activerecord_class);
    }

    public function model_iterator(): iterable
    {
        foreach ($this->definitions as $activerecord_class => $definition) {
            yield $activerecord_class => new ModelAccessor(
                $definition,
                isset($this->instances[$activerecord_class]),
                $this,
            );
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
        foreach ($this->model_iterator() as $accessor) {
            $model = $accessor->get();

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
        foreach ($this->model_iterator() as $accessor) {
            $model = $accessor->get();

            if (!$model->is_installed()) {
                continue;
            }

            $model->uninstall();
        }
    }

    /**
     * Check if models are installed.
     *
     * @return array<class-string<ActiveRecord>, bool>
     *     An array of key/value pairs where _key_ is a record class and
     *     _value_ `true` if the model is installed, `false` otherwise.
     */
    public function is_installed(): array
    {
        $rc = [];

        foreach ($this->model_iterator() as $activerecord_class => $defined) {
            $rc[$activerecord_class] = $defined->get()->is_installed();
        }

        return $rc;
    }
}
