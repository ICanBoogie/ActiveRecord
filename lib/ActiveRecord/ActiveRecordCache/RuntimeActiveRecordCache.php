<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\ActiveRecordCache;

use ArrayIterator;
use ICanBoogie\ActiveRecord;
use IteratorAggregate;

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
     * @return iterable<int|string, ActiveRecord>
     */
    public function getIterator(): iterable
    {
        return new ArrayIterator($this->records);
    }
}
