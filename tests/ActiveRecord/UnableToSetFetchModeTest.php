<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\UnableToSetFetchMode;
use PDO;
use PHPUnit\Framework\TestCase;

final class UnableToSetFetchModeTest extends TestCase
{
    public function test_get_id(): void
    {
        $mode = [ PDO::FETCH_ASSOC ];
        $e = new UnableToSetFetchMode($mode);
        $this->assertEquals($mode, $e->mode);
    }
}
