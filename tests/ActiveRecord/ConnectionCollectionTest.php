<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ConnectionNotEstablished;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Fixtures;

use function uniqid;

#[Group("db")]
final class ConnectionCollectionTest extends TestCase
{
    private ConnectionCollection $connections;

    protected function setUp(): void
    {
        $this->connections = new ConnectionCollection([
            new ConnectionDefinition(
                id: 'one',
                dsn: Fixtures::dsn(),
                username: Fixtures::username(),
                password: Fixtures::password(),
            ),
            new ConnectionDefinition(id: 'bad', dsn: 'mysql:dbname=bad_database' . uniqid()),
        ]);
    }

    public function test_connection_for_id(): void
    {
        $get = $this->connections->connection_for_id(...);
        $actual = $get('one');

        $this->assertSame('one', $actual->id);
        $this->assertSame($actual, $get('one'));
    }

    public function test_definitions_are_indexed_by_id(): void
    {
        $this->assertEquals([ 'one', 'bad' ], array_keys($this->connections->definitions));
    }

    public function test_should_fail_to_establish_connection(): void
    {
        $this->expectException(ConnectionNotEstablished::class);

        $this->connections->connection_for_id('bad');
    }

    public function test_iterator(): void
    {
        $connections = new ConnectionCollection([
            new ConnectionDefinition(
                id: 'one',
                dsn: Fixtures::dsn(),
                username: Fixtures::username(),
                password: Fixtures::password(),
            ),
            new ConnectionDefinition(
                id: 'two',
                dsn: Fixtures::dsn(),
                username: Fixtures::username(),
                password: Fixtures::password(),
            ),
        ]);

        $actual = [];

        foreach ($connections->connection_iterator() as $id => $defined) {
            $actual[$id] = $defined->instantiated;

            $this->assertEquals($id, $defined->get()->id);
        }

        $this->assertEquals([ 'one' => false, 'two' => false ], $actual);

        $actual = [];

        foreach ($connections->connection_iterator() as $id => $defined) {
            $actual[$id] = $defined->instantiated;
        }

        $this->assertEquals([ 'one' => true, 'two' => true ], $actual);
    }
}
