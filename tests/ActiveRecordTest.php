<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelProvider;
use ICanBoogie\ActiveRecord\ModelProviderWithClosure;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\ActiveRecordTest\Sample;
use Test\ICanBoogie\ActiveRecordTest\ValidateCase;

use function serialize;
use function uniqid;

#[Group("record")]
final class ActiveRecordTest extends TestCase
{
    private Model $model;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('nodes');

        $this->model = $models->model_for_record(Node::class);
        $this->model->install();

        StaticModelProvider::set(fn() => new ModelProviderWithClosure(fn() => $this->model));
    }

    public function test_query(): void
    {
        $actual = Node::query();

        $this->assertSame($this->model, $actual->model);
    }

    public function test_where(): void
    {
        $actual = Node::where([ 'title' => "foo" ]);

        $this->assertSame($this->model, $actual->model);
        $this->assertSame([ "foo" ], $actual->conditions_args);
    }

    public function test_get_model(): void
    {
        $sut = new Node();

        $this->assertSame($this->model, $sut->model);
    }

    public function test_save(): void
    {
        $sut = new Node();
        $sut->title = "madonna";
        $sut->save();

        $nid = $sut->nid;

        $this->assertNotNull($nid);
        $this->assertEquals($nid, $sut->primary_key_value);

        $sut->title = "madonna 2";
        $sut->save();

        $this->assertEquals($nid, $sut->nid);
        $this->assertEquals($nid, $sut->primary_key_value);
    }

    public function test_should_use_provided_model(): void
    {
        $record = new Node($this->model);
        $this->assertSame($this->model, $record->model);
    }

    public function test_model_is_resolved_with_resolver(): void
    {
        $resolver = $this->createMock(ModelProvider::class);
        $resolver->method('model_for_record')
            ->with(Node::class)
            ->willReturn($this->model);

        StaticModelProvider::set(fn() => $resolver);

        $record = new Node();

        $this->assertSame($this->model, $record->model);
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

    public function test_delete_missing_primary(): void
    {
        $record = new Node($this->model);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Unable to delete record, the primary key is not defined");
        $record->delete();
    }

    #[Group("validate")]
    public function test_validate(): void
    {
        $record = new ValidateCase($this->model);

        try {
            $record->save();
        } catch (RecordNotValid $e) {
            $errors = $e->errors->to_array();

            $this->assertArrayNotHasKey('id', $errors);
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('timezone', $errors);

            return;
        }

        $this->fail("Expected RecordNotValid");
    }
}
