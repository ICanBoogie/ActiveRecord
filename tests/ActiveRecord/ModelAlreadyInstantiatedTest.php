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

use ICanBoogie\ActiveRecord\ModelAlreadyInstantiated;
use PHPUnit\Framework\TestCase;

final class ModelAlreadyInstantiatedTest extends TestCase
{
    public function test_get_id(): void
    {
        $id = 'testing';
        $e = new ModelAlreadyInstantiated($id);
        $this->assertEquals($id, $e->id);
    }
}
