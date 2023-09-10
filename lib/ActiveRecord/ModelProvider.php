<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @phpstan-return Model<int|string|string[], T>
     */
    public function model_for_record(string $activerecord_class): Model;
}
