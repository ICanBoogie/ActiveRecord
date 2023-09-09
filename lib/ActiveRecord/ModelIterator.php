<?php

namespace ICanBoogie\ActiveRecord;

/**
 * An interface to provide a model iterator.
 */
interface ModelIterator
{
    /**
     * @template T of Model
     *
     * @return iterable<class-string<T>, (callable(): T)>
     */
    public function model_iterator(): iterable;
}
