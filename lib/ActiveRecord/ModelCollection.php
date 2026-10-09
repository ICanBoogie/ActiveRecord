<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;
use LogicException;
use Throwable;

use function array_keys;
use function array_reverse;
use function implode;

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
        foreach ($this->classes_in_install_order() as $activerecord_class) {
            $model = $this->model_for_record($activerecord_class);

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
        foreach (array_reverse($this->classes_in_install_order()) as $activerecord_class) {
            $model = $this->model_for_record($activerecord_class);

            if (!$model->is_installed()) {
                continue;
            }

            $model->uninstall();
        }
    }

    /**
     * Returns the record classes ordered so that the tables referenced by foreign keys come before
     * the tables referencing them.
     *
     * @return list<class-string<ActiveRecord>>
     *
     * @throws LogicException if foreign keys reference each other in a cycle.
     */
    private function classes_in_install_order(): array
    {
        $class_by_table = [];

        foreach ($this->definitions as $activerecord_class => $definition) {
            $class_by_table[$definition->connection][$definition->table->name] = $activerecord_class;
        }

        $ordered = [];
        $visiting = [];

        foreach (array_keys($this->definitions) as $activerecord_class) {
            $this->visit_for_install($activerecord_class, $class_by_table, $ordered, $visiting);
        }

        return array_keys($ordered);
    }

    /**
     * Depth-first visit of the tables referenced by foreign keys.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     * @param array<string, array<string, class-string<ActiveRecord>>> $class_by_table
     *     Record classes by connection and table name.
     * @param array<class-string<ActiveRecord>, true> $ordered
     * @param array<class-string<ActiveRecord>, true> $visiting
     */
    private function visit_for_install(
        string $activerecord_class,
        array $class_by_table,
        array &$ordered,
        array &$visiting,
    ): void {
        if (isset($ordered[$activerecord_class])) {
            return;
        }

        if (isset($visiting[$activerecord_class])) {
            throw new LogicException(
                "Unable to order the installation of the models, foreign keys form a cycle: "
                . implode(' -> ', [ ...array_keys($visiting), $activerecord_class ])
            );
        }

        $visiting[$activerecord_class] = true;
        $definition = $this->definitions[$activerecord_class];

        foreach ($definition->table->schema->foreign_keys as $foreign_key) {
            $dependency = $class_by_table[$definition->connection][$foreign_key->table] ?? null;

            // A table can reference itself, and the referenced table might not be part of the collection.
            if ($dependency && $dependency !== $activerecord_class) {
                $this->visit_for_install($dependency, $class_by_table, $ordered, $visiting);
            }
        }

        unset($visiting[$activerecord_class]);
        $ordered[$activerecord_class] = true;
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
