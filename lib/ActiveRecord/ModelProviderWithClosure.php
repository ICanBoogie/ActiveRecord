<?php

namespace ICanBoogie\ActiveRecord;

use Closure;

/**
 * Creates a {@see ModelProvider} from a closure.
 */
final readonly class ModelProviderWithClosure implements ModelProvider
{
    /**
     * @param Closure(string):Model $closure
     */
    public function __construct(
        private Closure $closure
    ) {
    }

    public function model_for_record(string $activerecord_class): Model
    {
        return ($this->closure)($activerecord_class);
    }
}
