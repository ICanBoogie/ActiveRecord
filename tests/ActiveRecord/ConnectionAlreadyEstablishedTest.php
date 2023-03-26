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

use ICanBoogie\ActiveRecord\ConnectionAlreadyEstablished;
use PHPUnit\Framework\TestCase;

final class ConnectionAlreadyEstablishedTest extends TestCase
{
    public function test_get_id(): void
    {
        $id = 'testing';
        $e = new ConnectionAlreadyEstablished($id);
        $this->assertEquals($id, $e->id);
    }
}
