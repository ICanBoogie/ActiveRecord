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
