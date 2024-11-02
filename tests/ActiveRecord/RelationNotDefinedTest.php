<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\RelationCollection;
use ICanBoogie\ActiveRecord\RelationNotDefined;
use PHPUnit\Framework\TestCase;

final class RelationNotDefinedTest extends TestCase
{
    public function test_exception(): void
    {
        $relation_name = uniqid();
        $relations = new class() extends RelationCollection {
            public function __construct() {
            }
        };

        $exception = new RelationNotDefined($relation_name, $relations);

        $this->assertSame($relation_name, $exception->relation_name);
        $this->assertSame($relations, $exception->collection);
    }
}
