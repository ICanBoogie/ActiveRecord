<?php

namespace Test\ICanBoogie\ActiveRecord\Validate\Validator;

use ICanBoogie\ActiveRecord\StaticModelProvider;
use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use ICanBoogie\ActiveRecord\Validate\Validator\Unique;
use ICanBoogie\Validate\Context;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group('validate')]
#[Group('db')]
final class UniqueTest extends DbTestCase
{
    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_normalize_options(): void
    {
        $column = uniqid();
        $validator = new Unique();
        $options = $validator->normalize_params([ Unique::OPTION_COLUMN => $column ]);
        $this->assertArrayHasKey(Unique::OPTION_COLUMN, $options);
        $this->assertArrayNotHasKey(0, $options);
        $this->assertSame($column, $options[Unique::OPTION_COLUMN]);
    }

    public function test_unique(): void
    {
        $models = Fixtures::only_models('nodes');

        $models->install();
        StaticModelProvider::set(fn() => $models);

        $record = new Node();
        $record->title = $title = 'A title';
        $record->save();

        $context = new Context();
        $context->attribute = 'title';
        $context->reader = new RecordAdapter($record);
        $context->validator_params = [ Unique::OPTION_COLUMN => 'title' ];

        $validator = new Unique();
        $actual = $validator->validate($title, $context);
        $this->assertTrue($actual);

        $record = new Node();
        $record->title = $title;

        $context->reader = new RecordAdapter($record);

        $actual = $validator->validate($title, $context);
        $this->assertFalse($actual);
    }
}
