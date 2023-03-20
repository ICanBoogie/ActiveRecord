<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

use ICanBoogie\Acme\Node;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelResolver;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaColumn;
use ICanBoogie\ActiveRecord\StaticModelResolver;
use ICanBoogie\ActiveRecordTest\Sample;
use ICanBoogie\ActiveRecordTest\ValidateCase;
use LogicException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Fixtures;

use function mt_rand;
use function serialize;
use function strrev;
use function uniqid;

/**
 * @group record
 */
final class ActiveRecordTest extends TestCase
{
    private Model $model;

    protected function setUp(): void
    {
        [ , $models ] = Fixtures::only_models([ 'nodes' ]);

        $this->model = $models->model_for_id('nodes');
    }

    public function test_should_use_provided_model(): void
    {
        $record = new Node($this->model);
        $this->assertSame($this->model, $record->model);
    }

    public function test_model_is_resolved_with_resolver(): void
    {
        $resolver = $this->createMock(ModelResolver::class);
        $resolver->method('model_for_activerecord')
            ->with(Node::class)
            ->willReturn($this->model);

        StaticModelResolver::define(fn() => $resolver);

        $record = new Node();

        $this->assertSame($this->model, $record->model);
        $this->assertSame($this->model->id, $record->model_id);
    }

    public function test_sleep_should_remove_model(): void
    {
        $record = new Node($this->model);
        $array = $record->__sleep();

        $this->assertArrayNotHasKey('model', $array);
    }

    public function test_serialize_should_remove_model_info(): void
    {
        $record = new Sample($this->model);
        $serialized_record = serialize($record);

        $this->assertStringNotContainsString('"model"', $serialized_record);
        $this->assertStringNotContainsString('"model_id"', $serialized_record);
    }

    public function test_debug_info_should_exclude_model(): void
    {
        $record = new Node($this->model);
        $record->title = uniqid();

        $array = $record->__debugInfo();
        $this->assertArrayNotHasKey("\0" . ActiveRecord::class . "\0model", $array);
        $this->assertArrayHasKey('title', $array);
    }

    public function test_save(): void
    {
        $this->markTestSkipped("doesn't work with readonly parent");

        $id = mt_rand(10000, 100000);
        $reverse = uniqid();
        $primary = 'id';
        $allow_null_with_value = uniqid();

        $model = $this
            ->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'save', 'get_id' ])
            ->addMethods([ 'get_extended_schema' ])
            ->getMock();
        $model
            ->method('get_id')
            ->willReturn('madonna');
        $model
            ->method('get_extended_schema')
            ->willReturn(new Schema([

                $primary => SchemaColumn::serial(primary: true),
                'reversed' => SchemaColumn::varchar(),
                'date' => SchemaColumn::datetime(),
                'do_not_allow_null' => SchemaColumn::varchar(),
                'allow_null' => SchemaColumn::varchar(null: true),
                'allow_null_with_value' => SchemaColumn::varchar(null: true),

            ]));
        $model
            ->expects($this->once())
            ->method('save')
            ->with([

                'reverse' => strrev($reverse),
                'allow_null' => null,
                'allow_null_with_value' => $allow_null_with_value,

            ])
            ->willReturn($id);

        $record = new Sample($model);
        $record->reverse = $reverse;
        $record->{'do_not_allow_null'} = null;
        $record->{'allow_null'} = null;
        $record->{'allow_null_with_value'} = $allow_null_with_value;

        $this->assertSame($id, $record->save());
    }

    public function test_delete_missing_primary(): void
    {
        $record = new Node($this->model);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unable to delete record, the primary key is not defined");
        $record->delete();
    }

    /**
     * @group validate
     */
    public function test_validate(): void
    {
        $record = new ValidateCase($this->model);

        try {
            $record->save();
        } catch (RecordNotValid $e) {
            $errors = $e->errors;

            $this->assertArrayNotHasKey('id', $errors);
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('timezone', $errors);

            return;
        }

        $this->fail("Expected RecordNotValid");
    }
}
