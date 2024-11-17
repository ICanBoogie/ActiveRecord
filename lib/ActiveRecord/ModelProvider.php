<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;

/**
 * Provides models.
 */
interface ModelProvider
{
    /**
     * Returns the Model for an ActiveRecord.
     *
     * @template T of ActiveRecord
     *
     * @param class-string<T> $activerecord_class
     *
     * @phpstan-return Model<T>
     */
    public function model_for_record(string $activerecord_class): Model;
}
