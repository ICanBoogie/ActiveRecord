<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\Validator;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use ICanBoogie\Validate\Context;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @group validate
 * @medium
 */
class UniqueTest extends TestCase
{
    public function test_normalize_options()
    {
        $column = uniqid();
        $validator = new Unique();
        $options = $validator->normalize_params([ Unique::OPTION_COLUMN => $column ]);
        $this->assertArrayHasKey(Unique::OPTION_COLUMN, $options);
        $this->assertArrayNotHasKey(0, $options);
        $this->assertSame($column, $options[Unique::OPTION_COLUMN]);
    }

    /**
     * @dataProvider provide_test_validate
     */
    public function test_validate(?string $column, bool $should_use_primary)
    {
        $attribute = 'attribute' . uniqid();
        $value = 'value' . uniqid();
        $primary = 'primary' . uniqid();
        $key = $should_use_primary ? 'key' . uniqid() : null;
        $context = $this->makeContext($attribute, $value, $column ?: $attribute, $primary, $key);
        $context->validator_params = [ Unique::OPTION_COLUMN => $column ];

        $validator = new Unique();
        $validator->validate($value, $context);
    }

    public function provide_test_validate(): array
    {
        return [

            [ null, false ],
            [ 'email', false ],
            [ null, true ],
            [ 'email', true ],

        ];
    }

    public function test_validate_with_invalid_reader()
    {
        $context = new Context();
        $context->attribute = uniqid();
        $context->reader = new class () {
        };

        $validator = new Unique();
        $this->expectException(InvalidArgumentException::class);
        $validator->validate(uniqid(), $context);
    }

    private function makeContext(
        string $attribute,
        string $value,
        string $column,
        string $primary,
        string $key = null
    ): Context {
        $context = new Context();
        $context->reader = $this->mockReader($value, $column, $primary, $key);
        $context->attribute = $attribute;

        return $context;
    }

    private function mockReader(
        string $value,
        string $column,
        string $primary,
        string $key = null
    ): RecordAdapter {
        $expected = true;

        $model = $this
            ->getMockBuilder(ActiveRecord\Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this
            ->getMockBuilder(ActiveRecord\Query::class)
            ->setConstructorArgs([ $model ])
            ->onlyMethods([ 'get_exists' ])
            ->getMock();
        $query
            ->expects($this->once())
            ->method('get_exists')
            ->willReturn($expected);

        $model = $this
            ->getMockBuilder(ActiveRecord\Model::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'get_id', 'get_primary' ])
            ->addMethods([ 'where' ])
            ->getMock();
        $model
            ->method('get_id')
            ->willReturn("madonna");
        $model
            ->method('get_primary')
            ->willReturn($primary);
        $model
            ->expects($this->once())
            ->method('where')
            ->with($key ? [ $column => $value, "!$primary" => $key ] : [ $column => $value ])
            ->willReturn($query);

        $record = new ActiveRecord($model);
        $record->$primary = $key;

        return new RecordAdapter($record);
    }
}
