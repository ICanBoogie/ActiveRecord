<?php

namespace ICanBoogie\ActiveRecord\ActiveRecordCache;

use ArrayIterator;
use ICanBoogie\ActiveRecord;
use IteratorAggregate;
use Traversable;

/**
 * Cache records during run time.
 *
 * @implements IteratorAggregate<int|string, ActiveRecord>
 */
class RuntimeActiveRecordCache extends AbstractActiveRecordCache implements IteratorAggregate
{
    /**
     * Cached records.
     *
     * @var array<int|string, ActiveRecord>
     */
    private array $records = [];

    /**
     * @inheritdoc
     */
    public function store(ActiveRecord $record): void
    {
        $key = $record->{$this->model->primary};

        if (!$key) {
            return;
        }

        $this->records[$key] = $record;
    }

    /**
     * @inheritdoc
     */
    public function retrieve(string|int $key): ?ActiveRecord
    {
        return $this->records[$key] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function eliminate(string|int $key): void
    {
        unset($this->records[$key]);
    }

    /**
     * @inheritdoc
     */
    public function clear(): void
    {
        $this->records = [];
    }

    /**
     * @return Traversable<int|string, ActiveRecord>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->records);
    }
}
