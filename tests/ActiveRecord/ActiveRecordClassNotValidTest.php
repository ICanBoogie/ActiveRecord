<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ActiveRecordClassNotValid;
use PHPUnit\Framework\TestCase;

final class ActiveRecordClassNotValidTest extends TestCase
{
    public function test_get_class(): void
    {
        $expected = ActiveRecord::class;
        $e = new ActiveRecordClassNotValid($expected);
        $this->assertEquals($expected, $e->class);
    }
}
