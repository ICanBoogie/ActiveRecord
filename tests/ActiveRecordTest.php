<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelResolver;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\StaticModelResolver;
use LogicException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\NodeModel;
use Test\ICanBoogie\ActiveRecordTest\Sample;
use Test\ICanBoogie\ActiveRecordTest\ValidateCase;

use function serialize;
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

        $this->model = $models->model_for_class(NodeModel::class);
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
