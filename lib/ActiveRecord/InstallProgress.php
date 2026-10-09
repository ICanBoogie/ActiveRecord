<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use Throwable;

/**
 * Receives the progress of {@see ModelInstaller::install()}, one call per model, in install order.
 */
interface InstallProgress
{
    /**
     * The table of the model already exists, nothing was done.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     */
    public function already_installed(string $activerecord_class): void;

    /**
     * The model is about to be installed, followed by {@see installed()} or {@see failed()}.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     */
    public function installing(string $activerecord_class): void;

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     */
    public function installed(string $activerecord_class): void;

    /**
     * The model could not be installed.
     *
     * Throw to abort the installation. Return to continue with the models that don't depend on
     * this one, they are reported with {@see skipped()}.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     */
    public function failed(string $activerecord_class, Throwable $error): void;

    /**
     * The model was not installed because a model it depends on failed or was skipped.
     *
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<ActiveRecord> $dependency
     *     The model this one depends on, its parent or a model referenced by a foreign key.
     */
    public function skipped(string $activerecord_class, string $dependency): void;
}
