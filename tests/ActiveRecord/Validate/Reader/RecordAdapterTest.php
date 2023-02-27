<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\Reader;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use PHPUnit\Framework\TestCase;

/**
 * @group validate
 * @small
 */
final class RecordAdapterTest extends TestCase
{
    public function test_adapter(): void
    {
        $p = 'property' . uniqid();
        $v = uniqid();

        $record = new ActiveRecord($this->mockModel());
        $record->$p = $v;

        $reader = new RecordAdapter($record);

        $this->assertSame($record, $reader->record);
        $this->assertSame($v, $record->$p);
        $this->assertNull($reader->read('p' . uniqid()));
    }

    private function mockModel(): Model
    {
        $model = $this->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->onlyMethods([ 'get_id'])
            ->getMock();
        $model->method('get_id')
            ->willReturn('acme');

        return $model;
    }
}
