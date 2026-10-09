<?php

namespace ICanBoogie\ActiveRecord;

use Throwable;

/**
 * The default progress of {@see ModelInstaller::install()}, aborts on the first failure.
 */
final class ThrowingInstallProgress implements InstallProgress
{
    public function already_installed(string $activerecord_class): void
    {
    }

    public function installing(string $activerecord_class): void
    {
    }

    public function installed(string $activerecord_class): void
    {
    }

    /**
     * @throws Throwable
     */
    public function failed(string $activerecord_class, Throwable $error): void
    {
        throw $error;
    }

    public function skipped(string $activerecord_class, string $dependency): void
    {
    }
}
