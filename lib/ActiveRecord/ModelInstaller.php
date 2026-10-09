<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use LogicException;
use Throwable;

use function array_keys;
use function array_reverse;
use function get_parent_class;
use function implode;
use function is_subclass_of;

/**
 * Installs and uninstalls models, in the order required by their dependencies: their parent, and
 * the models referenced by their foreign keys.
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
     * @param InstallProgress $progress
     *     Receives the progress of the installation, and decides whether a failure aborts it. By
     *     default, the first failure is thrown.
     *
     * @throws Throwable
     */
    public function install(InstallProgress $progress = new ThrowingInstallProgress()): void
    {
        [ $accessors, $dependencies ] = $this->resolve_install_order();

        /**
         * Models that failed or were skipped.
         *
         * @var array<class-string<ActiveRecord>, true> $not_installed
         */
        $not_installed = [];

        foreach ($accessors as $activerecord_class => $accessor) {
            foreach ($dependencies[$activerecord_class] as $dependency) {
                if (isset($not_installed[$dependency])) {
                    $not_installed[$activerecord_class] = true;
                    $progress->skipped($activerecord_class, $dependency);

                    continue 2;
                }
            }

            $model = $accessor->get();

            if ($model->is_installed()) {
                $progress->already_installed($activerecord_class);

                continue;
            }

            $progress->installing($activerecord_class);

            try {
                $model->install();
            } catch (Throwable $e) {
                $not_installed[$activerecord_class] = true;
                $progress->failed($activerecord_class, $e);

                continue;
            }

            $progress->installed($activerecord_class);
        }
    }

    /**
     * Uninstall all the models.
     *
     * @throws Throwable
     */
    public function uninstall(): void
    {
        [ $accessors ] = $this->resolve_install_order();

        foreach (array_reverse($accessors) as $accessor) {
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
     * Returns the model accessors ordered so that the models come after their dependencies, along
     * with these dependencies.
     *
     * @return array{
     *     array<class-string<ActiveRecord>, ModelAccessor>,
     *     array<class-string<ActiveRecord>, list<class-string<ActiveRecord>>>
     * }
     *
     * @throws LogicException if dependencies form a cycle.
     */
    private function resolve_install_order(): array
    {
        $accessors = [];
        $class_by_table = [];

        foreach ($this->models->model_iterator() as $activerecord_class => $accessor) {
            $definition = $accessor->definition;
            $accessors[$activerecord_class] = $accessor;
            $class_by_table[$definition->connection][$definition->table->name] = $activerecord_class;
        }

        $dependencies = [];

        foreach ($accessors as $activerecord_class => $accessor) {
            $dependencies[$activerecord_class] = $this->resolve_dependencies(
                $activerecord_class,
                $accessor,
                $accessors,
                $class_by_table,
            );
        }

        $ordered = [];
        $visiting = [];

        foreach (array_keys($accessors) as $activerecord_class) {
            $this->visit_for_install($activerecord_class, $accessors, $dependencies, $ordered, $visiting);
        }

        return [ $ordered, $dependencies ];
    }

    /**
     * Returns the models a model depends on: its parent, and the models referenced by its
     * foreign keys.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     * @param array<class-string<ActiveRecord>, ModelAccessor> $accessors
     * @param array<string, array<string, class-string<ActiveRecord>>> $class_by_table
     *     Record classes by connection and table name.
     *
     * @return list<class-string<ActiveRecord>>
     */
    private function resolve_dependencies(
        string $activerecord_class,
        ModelAccessor $accessor,
        array $accessors,
        array $class_by_table,
    ): array {
        $dependencies = [];
        $parent_class = get_parent_class($activerecord_class);

        if ($parent_class && is_subclass_of($parent_class, ActiveRecord::class) && isset($accessors[$parent_class])) {
            $dependencies[] = $parent_class;
        }

        $definition = $accessor->definition;

        foreach ($definition->table->schema->foreign_keys as $foreign_key) {
            $dependency = $class_by_table[$definition->connection][$foreign_key->table] ?? null;

            // A table can reference itself, and the referenced table might not be part of the iterator.
            if ($dependency && $dependency !== $activerecord_class) {
                $dependencies[] = $dependency;
            }
        }

        return $dependencies;
    }

    /**
     * Depth-first visit of the dependencies.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     * @param array<class-string<ActiveRecord>, ModelAccessor> $accessors
     * @param array<class-string<ActiveRecord>, list<class-string<ActiveRecord>>> $dependencies
     * @param array<class-string<ActiveRecord>, ModelAccessor> $ordered
     * @param array<class-string<ActiveRecord>, true> $visiting
     */
    private function visit_for_install(
        string $activerecord_class,
        array $accessors,
        array $dependencies,
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

        foreach ($dependencies[$activerecord_class] as $dependency) {
            $this->visit_for_install($dependency, $accessors, $dependencies, $ordered, $visiting);
        }

        unset($visiting[$activerecord_class]);
        $ordered[$activerecord_class] = $accessors[$activerecord_class];
    }
}
