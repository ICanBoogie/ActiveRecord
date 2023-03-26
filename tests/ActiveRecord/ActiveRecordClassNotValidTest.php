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
