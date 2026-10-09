<?php

namespace Test\ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\Integer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\SetStateHelper;

final class BooleanTest extends TestCase
{
    #[DataProvider('provideExport')]
    public function testExport(Boolean $expected): void
    {
        $actual = SetStateHelper::export_import($expected);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array<array{ Boolean }>
     */
    public static function provideExport(): array
    {
        return [
            [ new Boolean(null: true) ],
            [ new Boolean(default: true) ],
            [ new Boolean(default: false) ],
        ];
    }

    /**
     * A boolean is not an integer, so checks such as `instanceof Integer` don't apply to it.
     */
    public function testIsNotInteger(): void
    {
        $actual = new Boolean();

        $this->assertInstanceOf(Column::class, $actual);
        $this->assertNotInstanceOf(Integer::class, $actual);
    }

    #[DataProvider('provideDefault')]
    public function testDefault(?bool $default, ?string $expected): void
    {
        $actual = new Boolean(default: $default);

        $this->assertSame($expected, $actual->default);
    }

    /**
     * @return array<array{ ?bool, ?string }>
     */
    public static function provideDefault(): array
    {
        return [
            [ null, null ],
            [ true, Boolean::TRUE ],
            [ false, Boolean::FALSE ],
        ];
    }
}
