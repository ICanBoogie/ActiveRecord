<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;

/**
 * An interface to provide a model iterator.
 */
interface ModelIterator
{
    /**
     * @return iterable<class-string<ActiveRecord>, ModelAccessor>
     */
    public function model_iterator(): iterable;
}
