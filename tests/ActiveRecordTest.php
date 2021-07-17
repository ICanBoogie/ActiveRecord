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

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelProvider;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecordTest\Sample;
use ICanBoogie\ActiveRecordTest\ValidateCase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

use function mt_rand;
use function serialize;
use function strrev;
use function uniqid;
use function unserialize;

/**
 * @group record
 */
class ActiveRecordTest extends TestCase
{
    use ConnectionHelper;

    private static $sample_model;

    protected function setUp(): void
    {
        if (self::$sample_model) {
            return;
        }

        self::$sample_model = $sample_model = $this->mockModel();

        ModelProvider::define(function (string $model_id) use ($sample_model): ?Model {
            if ($model_id === 'sample') {
                return $sample_model;
            }

            return null;
        });
    }

    public function test_should_resolve_model_id_from_const()
    {
        $record = new Sample();
        $this->assertSame($record->model_id, Sample::MODEL_ID);
    }

    public function test_should_resolve_model_from_const()
    {
        $record = new Sample();
        $this->assertSame(self::$sample_model, $record->model);
    }

    public function test_should_use_provided_model()
    {
        $model = $this->mockModel();
        $record = new Sample($model);
        $this->assertSame($model, $record->model);
    }

    public function test_should_throw_exception_on_invalid_model()
    {
        $this->expectException(InvalidArgumentException::class);
        new Sample(123);
    }

    public function test_sleep_should_remove_model()
    {
        $model = $this->mockModel();
        $record = new Sample($model);
        $array = $record->__sleep();

        $this->assertArrayNotHasKey('model', $array);
    }

    public function test_sleep_should_remove_any_instance_of_self()
    {
        $model = $this->mockModel();
        $property = 'p' . uniqid();
        $record = new Sample($model);
        $record->$property = new Sample($model);

        $array = $record->__sleep();

        $this->assertArrayNotHasKey($property, $array);
    }

    public function test_serialize_should_preserve_model_id()
    {
        $record = new ActiveRecord(self::$sample_model);
        $serialized_record = serialize($record);
        $unserialized_record = unserialize($serialized_record);

        $this->assertEquals($record->model_id, $unserialized_record->model_id);

        $record = new Sample(self::$sample_model);
        $serialized_record = serialize($record);
        $unserialized_record = unserialize($serialized_record);
        $this->assertEquals($record->model_id, $unserialized_record->model_id);
    }

    public function test_debug_info_should_exclude_model()
    {
        $model = $this->mockModel();
        $property = 'p' . uniqid();
        $record = new Sample($model);
        $record->$property = uniqid();

        $array = $record->__debugInfo();
        $this->assertArrayNotHasKey("\0" . ActiveRecord::class . "\0model", $array);
        $this->assertArrayHasKey($property, $array);
    }

    public function test_save()
    {
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

                $primary => 'serial',
                'reversed' => 'varchar',
                'date' => 'datetime',
                'do_not_allow_null' => [ 'varchar' ],
                'allow_null' => [ 'varchar', 'null' => true ],
                'allow_null_with_value' => [ 'varchar', 'null' => true ],

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

    public function test_delete_missing_primary()
    {
        $model = $this->mockModel();
        $record = new ActiveRecord($model);
        $this->expectException(LogicException::class);
        $record->delete();
    }

    private function mockModel(): Model
    {
        return $this
            ->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @group validate
     */
    public function test_validate()
    {
        $model = $this
            ->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        $record = new ValidateCase($model);

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
