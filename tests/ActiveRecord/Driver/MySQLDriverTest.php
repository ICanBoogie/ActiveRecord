<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Driver;

use ICanBoogie\ActiveRecord\Connection;
use ICanBoogie\ActiveRecord\Schema;

class MySQLDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @requires PHP 5.6
     */
    public function test_multicolumn_primary_key()
    {
        $this->markTestSkipped();

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods([ 'exec' ])
            ->getMock();
        $connection
            ->expects($this->any())
            ->method('exec')
            ->willReturnCallback(function (string $statement) {
                $this->assertStringContainsString("`id` BIGINT UNSIGNED NOT NULL", $statement);
                $this->assertStringContainsString("`name` VARCHAR( 255 ) NOT NULL", $statement);
                $this->assertStringContainsString("`value` TEXT NOT NULL", $statement);
                $this->assertStringContainsString("PRIMARY KEY(`id`, `name`)", $statement);
            });

        /* @var $connection Connection */

        $schema = new Schema([

            'id' => [ 'foreign', 'primary' => true ],
            'name' => [ 'varchar', 'primary' => true ],
            'value' => 'text'

        ]);

        $driver = new MySQLDriver(function () use ($connection) {
            return $connection;
        });

        $driver->create_table('test', $schema);
    }
}
