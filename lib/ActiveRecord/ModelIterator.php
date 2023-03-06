<?php

namespace ICanBoogie\ActiveRecord;

/**
 * An interface to provide a model iterator.
 */
interface ModelIterator
{
    /**
     * @return iterable<string, (callable(): Model)>
     */
    public function model_iterator(): iterable;
}
