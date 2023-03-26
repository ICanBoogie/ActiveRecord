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

use ICanBoogie\DateTime;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\ActiveRecord\CreatedAtPropertyTest\A;
use Test\ICanBoogie\ActiveRecord\CreatedAtPropertyTest\B;

final class CreatedAtPropertyTest extends TestCase
{
    /**
     * @dataProvider provide_test_property
     */
    public function test_property(string $classname): void
    {
        /* @var $r A */
        $r = new $classname();
        $datetime = new DateTime();

        $this->assertInstanceOf(DateTime::class, $r->created_at);
        $this->assertTrue($r->created_at->is_empty);

        $r->created_at = $datetime;

        $this->assertSame($datetime, $r->created_at);
        $this->assertArrayHasKey('created_at', $r->to_array());
        $this->assertSame($datetime, $r->to_array()['created_at']);
        $this->assertArrayHasKey('created_at', $r->__sleep());

        $r->created_at = null;
        $this->assertInstanceOf(DateTime::class, $r->created_at);
        $this->assertTrue($r->created_at->is_empty);
    }

    public static function provide_test_property()
    {
        return [

            [ A::class ],
            [ B::class ]

        ];
    }
}

namespace Test\ICanBoogie\ActiveRecord\CreatedAtPropertyTest;

use ICanBoogie\ActiveRecord\Property\CreatedAtProperty;
use ICanBoogie\Prototyped;

class A extends Prototyped
{
    use CreatedAtProperty;
}

class B extends A
{
}
