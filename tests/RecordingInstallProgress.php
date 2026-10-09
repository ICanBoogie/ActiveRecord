<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord\InstallProgress;
use Throwable;

/**
 * Records the progress of an installation, as a list of `[ state, record class, detail ]`.
 */
final class RecordingInstallProgress implements InstallProgress
{
    /**
     * @var list<array{ string, string, 2?: string }>
     */
    public array $events = [];

    public function already_installed(string $activerecord_class): void
    {
        $this->events[] = [ 'already_installed', $activerecord_class ];
    }

    public function installing(string $activerecord_class): void
    {
        $this->events[] = [ 'installing', $activerecord_class ];
    }

    public function installed(string $activerecord_class): void
    {
        $this->events[] = [ 'installed', $activerecord_class ];
    }

    public function failed(string $activerecord_class, Throwable $error): void
    {
        $this->events[] = [ 'failed', $activerecord_class, $error->getMessage() ];
    }

    public function skipped(string $activerecord_class, string $dependency): void
    {
        $this->events[] = [ 'skipped', $activerecord_class, $dependency ];
    }
}
