<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use LogicException;
use Throwable;

use function array_keys;
use function array_reverse;
use function array_values;
use function implode;

/**
 * Installs and uninstalls models, in the order required by their foreign keys.
 */
final readonly class ModelInstaller
{
    public function __construct(
        private ModelIterator $models,
    ) {
    }

    /**
     * Install all the models.
     *
     * @throws Throwable
     */
    public function install(): void
    {
        foreach ($this->accessors_in_install_order() as $accessor) {
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
        foreach (array_reverse($this->accessors_in_install_order()) as $accessor) {
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

        foreach ($this->models->model_iterator() as $activerecord_class => $accessor) {
            $rc[$activerecord_class] = $accessor->get()->is_installed();
        }

        return $rc;
    }

    /**
     * Returns the model accessors ordered so that the tables referenced by foreign keys come before
     * the tables referencing them.
     *
     * @return list<ModelAccessor>
     *
     * @throws LogicException if foreign keys reference each other in a cycle.
     */
    private function accessors_in_install_order(): array
    {
        $accessors = [];
        $class_by_table = [];

        foreach ($this->models->model_iterator() as $activerecord_class => $accessor) {
            $definition = $accessor->definition;
            $accessors[$activerecord_class] = $accessor;
            $class_by_table[$definition->connection][$definition->table->name] = $activerecord_class;
        }

        $ordered = [];
        $visiting = [];

        foreach (array_keys($accessors) as $activerecord_class) {
            $this->visit_for_install($activerecord_class, $accessors, $class_by_table, $ordered, $visiting);
        }

        return array_values($ordered);
    }

    /**
     * Depth-first visit of the tables referenced by foreign keys.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     * @param array<class-string<ActiveRecord>, ModelAccessor> $accessors
     * @param array<string, array<string, class-string<ActiveRecord>>> $class_by_table
     *     Record classes by connection and table name.
     * @param array<class-string<ActiveRecord>, ModelAccessor> $ordered
     * @param array<class-string<ActiveRecord>, true> $visiting
     */
    private function visit_for_install(
        string $activerecord_class,
        array $accessors,
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
        $accessor = $accessors[$activerecord_class];
        $definition = $accessor->definition;

        foreach ($definition->table->schema->foreign_keys as $foreign_key) {
            $dependency = $class_by_table[$definition->connection][$foreign_key->table] ?? null;

            // A table can reference itself, and the referenced table might not be part of the iterator.
            if ($dependency && $dependency !== $activerecord_class) {
                $this->visit_for_install($dependency, $accessors, $class_by_table, $ordered, $visiting);
            }
        }

        unset($visiting[$activerecord_class]);
        $ordered[$activerecord_class] = $accessor;
    }
}
