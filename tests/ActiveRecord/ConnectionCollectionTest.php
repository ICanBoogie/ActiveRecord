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

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ConnectionNotEstablished;
use PHPUnit\Framework\TestCase;

use function uniqid;

final class ConnectionCollectionTest extends TestCase
{
    private ConnectionCollection $connections;

    protected function setUp(): void
    {
        $this->connections = new ConnectionCollection([
            new ConnectionDefinition(id: 'one', dsn: 'sqlite::memory:'),
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
            new ConnectionDefinition(id: 'one', dsn: 'sqlite::memory:'),
            new ConnectionDefinition(id: 'two', dsn: 'sqlite::memory:'),
        ]);

        $actual = [];

        foreach ($connections->connection_iterator() as $id => $defined) {
            $actual[$id] = $defined->established;

            $this->assertEquals($id, $defined->connect()->id);
        }

        $this->assertEquals([ 'one' => false, 'two' => false ], $actual);

        $actual = [];

        foreach ($connections->connection_iterator() as $id => $defined) {
            $actual[$id] = $defined->established;
        }

        $this->assertEquals([ 'one' => true, 'two' => true ], $actual);
    }
}
