<?php

namespace ICanBoogie\ActiveRecord\ConnectionTelemetry;

readonly class ExecuteRecord
{
    /**
     * @param float $timestamp
     *     Unix timestamp of the execution, with microsecond granularity.
     * @param float $duration
     *     The Duration of the execution in second, with microsecond granularity.
     * @param string $statement
     *     The executed statement.
     */
    public function __construct(
        public float $timestamp,
        public float $duration,
        public string $statement,
    ) {
    }
}
