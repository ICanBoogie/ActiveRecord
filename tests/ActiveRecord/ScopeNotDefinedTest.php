<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;

class ScopeNotDefinedTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Model
     */
    private $model;

    /**
     * @var ScopeNotDefined
     */
    private $exception;

    protected function setUp(): void
    {
        $this->model = $this
            ->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'get_unprefixed_name' ])
            ->getMock();
        $this->model
            ->expects($this->once())
            ->method('get_unprefixed_name')
            ->willReturn('testing');

        $this->exception = new ScopeNotDefined('my_scope', $this->model);
    }

    public function test_message()
    {
        $this->assertEquals("Unknown scope `my_scope` for model `testing`.", $this->exception->getMessage());
    }

    public function test_get_scope_name()
    {
        $this->assertEquals('my_scope', $this->exception->scope_name);
    }

    public function test_get_model()
    {
        $this->assertSame($this->model, $this->exception->model);
    }
}
