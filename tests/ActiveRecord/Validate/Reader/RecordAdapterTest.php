<?php

namespace Test\ICanBoogie\ActiveRecord\Validate\Reader;

use ICanBoogie\ActiveRecord\StaticModelProvider;
use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Fixtures;

#[Group('validate')]
final class RecordAdapterTest extends TestCase
{
    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_adapter(): void
    {
        $models = Fixtures::only_models('nodes');

        $v = uniqid();

        StaticModelProvider::set(fn() => $models);

        $record = new Node();
        $record->title = $v;

        $reader = new RecordAdapter($record);

        $this->assertSame($record, $reader->record);
        $this->assertSame($v, $record->title);
        $this->assertNull($reader->read('title' . uniqid()));
    }
}
