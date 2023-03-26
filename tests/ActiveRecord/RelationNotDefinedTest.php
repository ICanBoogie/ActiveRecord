<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\RelationCollection;
use ICanBoogie\ActiveRecord\RelationNotDefined;
use PHPUnit\Framework\TestCase;

final class RelationNotDefinedTest extends TestCase
{
    public function test_exception(): void
    {
        $relation_name = uniqid();
        $collection = $this
            ->getMockBuilder(RelationCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        /* @var $collection RelationCollection */

        $exception = new RelationNotDefined($relation_name, $collection);

        $this->assertSame($relation_name, $exception->relation_name);
        $this->assertSame($collection, $exception->collection);
    }
}
