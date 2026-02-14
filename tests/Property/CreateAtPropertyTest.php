<?php

namespace Property;

use ICanBoogie\ActiveRecord\Property\CreatedAtProperty;
use ICanBoogie\DateTime;
use PHPUnit\Framework\TestCase;

final class CreateAtPropertyTest extends TestCase
{
    /**
     * @var object<CreatedAtProperty>
     */
    private object $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new class
        {
            use CreatedAtProperty;
        };
    }

    public function testEmpty()
    {
        $this->assertInstanceOf(DateTime::class, $this->sut->created_at);
        $this->assertTrue($this->sut->created_at->is_empty);
    }

    public function testSetter(): void
    {
        $sut = $this->sut;
        $now = DateTime::now();
        $sut->created_at = $now;
        $this->assertSame($now, $sut->created_at);

        $sut->created_at = null;
        $this->assertTrue($sut->created_at->is_empty);

        $sut->created_at = '2020-01-01';
        $this->assertEquals('2020-01-01', $sut->created_at->format('Y-m-d'));
    }
}
