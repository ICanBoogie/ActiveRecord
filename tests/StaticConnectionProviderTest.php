<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\StaticConnectionProvider;
use PHPUnit\Framework\TestCase;

final class StaticConnectionProviderTest extends TestCase
{
    public function test_set_unset(): void
    {
        $factory = fn() => new ConnectionCollection([]);
        StaticConnectionProvider::set($factory);
        $this->assertNotNull(StaticConnectionProvider::get());

        StaticConnectionProvider::reset();
        $this->assertNull(StaticConnectionProvider::get());
    }

    public function test_connection_for_id(): void
    {
        $id = "foo";
        $factory = fn() => new ConnectionCollection([
            new ConnectionDefinition(
                id: $id,
                dsn: "sqlite::memory:"
            )
        ]);
        StaticConnectionProvider::set($factory);

        $actual = StaticConnectionProvider::connection_for_id($id);

        $this->assertEquals($id, $actual->id);
    }
}
