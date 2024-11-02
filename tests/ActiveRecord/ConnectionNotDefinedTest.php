<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConnectionNotDefined;
use PHPUnit\Framework\TestCase;

class ConnectionNotDefinedTest extends TestCase
{
    public function test_get_id(): void
    {
        $id = 'testing';
        $e = new ConnectionNotDefined($id);
        $this->assertEquals($id, $e->id);
    }
}
