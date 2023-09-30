<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;

/**
 * An interface to provide a model iterator.
 */
interface ModelIterator
{
    /**
     * @template T of ActiveRecord
     *
     * @return iterable<class-string<T>, (callable(): Model<scalar|scalar[],T>)>
     */
    public function model_iterator(): iterable;
}
