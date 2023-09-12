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

use ICanBoogie\OffsetNotDefined;
use Throwable;

/**
 * Exception thrown in attempt to obtain a relation that is not defined.
 */
class RelationNotDefined extends OffsetNotDefined implements Exception
{
    /**
     * @param non-empty-string $relation_name
     *     Name of the undefined relation.
     */
    public function __construct(
        public readonly string $relation_name,
        public readonly RelationCollection $collection,
        Throwable $previous = null
    ) {
        parent::__construct([ $relation_name, $collection ], $previous);
    }
}
