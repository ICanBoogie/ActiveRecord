<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\ICanBoogie\ActiveRecord\Property;

use ICanBoogie\ActiveRecord\UpdatedAtPropertyTest\A;
use ICanBoogie\ActiveRecord\UpdatedAtPropertyTest\B;
use ICanBoogie\DateTime;
use PHPUnit\Framework\TestCase;

class UpdatedAtPropertyTest extends TestCase
{
    /**
     * @dataProvider provide_test_property
     */
    public function test_property($classname)
    {
        /* @var $r A|B */
        $r = new $classname();
        $datetime = new DateTime();

        $this->assertInstanceOf(DateTime::class, $r->updated_at);
        $this->assertTrue($r->updated_at->is_empty);

        $r->updated_at = $datetime;

        $this->assertSame($datetime, $r->updated_at);
        $this->assertArrayHasKey('updated_at', $r->to_array());
        $this->assertSame($datetime, $r->to_array()['updated_at']);
        $this->assertArrayHasKey('updated_at', $r->__sleep());

        $r->updated_at = null;
        $this->assertInstanceOf(DateTime::class, $r->updated_at);
        $this->assertTrue($r->updated_at->is_empty);
    }

    public static function provide_test_property()
    {
        return [

            [ A::class ],
            [ B::class ]

        ];
    }
}

namespace ICanBoogie\ActiveRecord\UpdatedAtPropertyTest;

use ICanBoogie\ActiveRecord\Property\UpdatedAtProperty;
use ICanBoogie\Prototyped;

class A extends Prototyped
{
    use UpdatedAtProperty;
}

class B extends A
{
}
