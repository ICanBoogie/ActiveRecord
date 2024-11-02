<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConnectionNotEstablished;
use PHPUnit\Framework\TestCase;

final class ConnectionNotEstablishedTest extends TestCase
{
    public function test_get_id(): void
    {
        $id = 'testing';
        $e = new ConnectionNotEstablished($id, "message");
        $this->assertEquals($id, $e->id);
    }
}
