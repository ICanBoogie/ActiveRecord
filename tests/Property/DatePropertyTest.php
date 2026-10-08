<?php

namespace Property;

use ICanBoogie\ActiveRecord\Property\DateProperty;
use ICanBoogie\DateTime;
use PHPUnit\Framework\TestCase;

final class DatePropertyTest extends TestCase
{
    private object $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new class
        {
            use DateProperty;
        };
    }

    public function testEmpty()
    {
        $this->assertInstanceOf(DateTime::class, $this->sut->date);
        $this->assertTrue($this->sut->date->is_empty);
    }

    public function testSetter(): void
    {
        $sut = $this->sut;
        $now = DateTime::now();
        $sut->date = $now;
        $this->assertSame($now, $sut->date);

        $sut->date = null;
        $this->assertTrue($sut->date->is_empty);

        $sut->date = '2020-01-01';
        $this->assertEquals('2020-01-01', $sut->date->format('Y-m-d'));
    }
}
