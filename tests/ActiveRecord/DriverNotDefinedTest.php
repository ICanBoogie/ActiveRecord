<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
