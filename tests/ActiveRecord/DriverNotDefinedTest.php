<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\DriverNotDefined;
use PHPUnit\Framework\TestCase;

final class DriverNotDefinedTest extends TestCase
{
    public function test_exception(): void
    {
        $driver_name = uniqid();
        $exception = new DriverNotDefined($driver_name);
        $this->assertSame($driver_name, $exception->driver_name);
        $this->assertStringContainsString($driver_name, $exception->getMessage());
    }
}
