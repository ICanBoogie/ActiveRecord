<?php

namespace ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\ConnectionTelemetry\ExecuteRecord;

final class ConnectionTelemetry
{
    /**
     * @var ExecuteRecord[]
     */
    public array $record_execute_time = [];

    /**
     * The number of database queries and executions, used for statistics purpose.
     */
    public int $execute_count = 0;

    public function __construct(
        public readonly string $connection_id,
    ) {
    }

    public function inc_execute_count(): void
    {
        $this->execute_count++;
    }

    /**
     * @template T
     *
     * @param Closure(): T $closure
     *
     * @return T
     */
    public function record_execute_duration(
        string $statement,
        Closure $closure
    ): mixed {
        $start = microtime(true);

        try {
            return $closure();
        } finally {
            $this->inc_execute_count();
            $this->record_execute_time[] = new ExecuteRecord(
                $start,
                microtime(true) - $start,
                $statement,
            );
        }
    }
}
