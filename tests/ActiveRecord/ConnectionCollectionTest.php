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
use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\ConnectionAlreadyEstablished;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ConnectionNotDefined;
use ICanBoogie\ActiveRecord\ConnectionNotEstablished;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function uniqid;

final class ConnectionCollectionTest extends TestCase
{
    private ConnectionCollection $connections;

    protected function setUp(): void
    {
        $this->connections = new ConnectionCollection([
            'one' => new ConnectionDefinition(id: 'one', dsn: 'sqlite::memory:'),
            'bad' => new ConnectionDefinition(id: 'bad', dsn: 'mysql:dbname=bad_database' . uniqid()),
        ]);
    }

    public function test_connection_for_id(): void
    {
        $actual = $this->connections->connection_for_id('one');

        $this->assertInstanceOf(Connection::class, $actual);
        $this->assertSame('one', $actual->id);
    }

    public function test_should_get_definitions(): void
    {
        $names = [];

        foreach ($this->connections->definitions as $name => $definition) {
            $names[] = $name;
        }

        $this->assertEquals([ 'one', 'bad' ], $names);
    }

    public function test_should_get_connection(): void
    {
        $connection = $this->connections['one'];
        $this->assertInstanceOf(Connection::class, $connection);
    }

    /**
     * @depends test_should_get_connection
     */
    public function test_should_throw_an_exception_on_setting_established_connection(): void
    {
        $this->assertInstanceOf(Connection::class, $this->connections['one']);
        $this->expectException(ConnectionAlreadyEstablished::class);
        $this->connections['one'] = [ 'dsn' => 'sqlite::memory:' ];
    }

    public function test_should_throw_an_exception_on_getting_undefined_connection(): void
    {
        $this->expectException(ConnectionNotDefined::class);
        $this->connections['undefined'];
    }

    public function test_should_set_connection_while_it_is_not_established(): void
    {
        $this->connections['two'] = new ConnectionDefinition('two', 'sqlite::memory:');

        $this->assertInstanceOf(Connection::class, $this->connections['two']);
    }

    public function test_should_throw_an_exception_on_setting_invalid_connection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->connections['invalid'] = [

            'd_s_n' => 'sqlite::memory:'
        ];
    }

    public function test_should_unset_connection_definition(): void
    {
        $this->connections['two'] = new ConnectionDefinition('two', 'sqlite::memory:');

        unset($this->connections['two']);

        $this->assertFalse(isset($this->connections['two']));
    }

    /**
     * @depends test_should_get_connection
     */
    public function test_should_throw_exception_on_unsetting_established_connection(): void
    {
        $this->connections['one'];
        $this->expectException(ConnectionAlreadyEstablished::class);
        unset($this->connections['one']);
    }

    public function testConnectionNotDefined(): void
    {
        $this->expectException(ConnectionNotDefined::class);
        $this->connections['two'];
    }

    public function testConnectionNotEstablished(): void
    {
        $this->expectException(ConnectionNotEstablished::class);
        $this->connections['bad'];
    }

    public function test_get_established(): void
    {
        $connections = new ConnectionCollection([

            'one' => new ConnectionDefinition('one', 'sqlite::memory:'),
            'two' => new ConnectionDefinition('two', 'sqlite::memory:'),

        ]);

        $this->assertEmpty($connections->established);

        $connection = $connections['one'];
        $this->assertSame($connection, $connections['one']);

        $this->assertSame([

            'one' => $connection

        ], $connections->established);
    }

    public function test_iterator(): void
    {
        $connections = $this->connections;
        $names = [];

        foreach ($connections as $id => $definition) {
            $name[] = $id;
        }

        $this->assertEmpty($names);
        $connection = $connections['one'];

        foreach ($connections as $id => $c) {
            $names[] = $id;
            $this->assertSame($connection, $c);
        }

        $this->assertCount(1, $names);
        $this->assertEquals([ 'one' ], $names);
    }
}
