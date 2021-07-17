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

/**
 * @group validate
 * @small
 */
class RecordAdapterTest extends \PHPUnit\Framework\TestCase
{
    public function test_adapter()
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

    /**
     * @return ActiveRecord\Model
     */
    private function mockModel()
    {
        return $this
            ->getMockBuilder(ActiveRecord\Model::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
