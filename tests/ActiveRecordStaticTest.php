<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Node;

final class ActiveRecordStaticTest extends TestCase
{
    protected function setUp(): void
    {
        $models = Fixtures::only_models('nodes');

        StaticModelProvider::define(fn() => $models);
    }

    protected function tearDown(): void
    {
        StaticModelProvider::undefine();
    }

    #[Test]
    public function query_returns_a_new_query(): void
    {
        $query = Node::query();

        $this->assertNotSame($query, Node::query());
        $this->assertEquals(Node::class, $query->model->activerecord_class);
    }

    #[Test]
    public function where_returns_a_new_query(): void
    {
        $query = Node::where("1 = 1");

        $this->assertNotSame($query, Node::where("1 = 1"));
        $this->assertEquals(Node::class, $query->model->activerecord_class);
    }
}
