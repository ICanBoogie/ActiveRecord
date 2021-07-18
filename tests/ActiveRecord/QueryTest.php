<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\DateTime;
use ICanBoogie\ActiveRecord\QueryTest\Dog;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    private static $n = 10;

    /**
     * @var Connection
     */
    private static $connection;

    /**
     * @var ModelCollection
     */
    private static $models;

    /**
     * @var Model
     */
    private static $animals;

    /**
     * @var Model
     */
    private static $dogs;
    private static $source;

    /**
     * @var ConnectionCollection
     */
    private $connections;

    public static function setUpBeforeClass(): void
    {
        $connections = new ConnectionCollection([

            'primary' => 'sqlite::memory:'

        ]);

        $models = new ModelCollection($connections, [

            'animals' => [
                Model::SCHEMA => new Schema([
                    'id' => SchemaColumn::serial(primary: true),
                    'name' => SchemaColumn::varchar(),
                    'date' => SchemaColumn::timestamp(),
                    'legs' => SchemaColumn::int(),
                ])
            ],

            'dogs' => [
                Model::ACTIVERECORD_CLASS => Dog::class,
                Model::EXTENDING => 'animals',
                Model::SCHEMA => new Schema([
                    'bark_volume' => SchemaColumn::float(),
                ])
            ],

            'subscribers' => [
                Model::SCHEMA => new Schema([
                    'subscriber_id' => SchemaColumn::serial(primary: true),
                    'email' => SchemaColumn::varchar(),
                ])
            ],

            'updates' => [
                Model::SCHEMA => new Schema([
                    'update_id' => SchemaColumn::serial(primary: true),
                    'subscriber_id' => SchemaColumn::foreign(),
                    'updated_at' => SchemaColumn::datetime(),
                    'update_hash' => SchemaColumn::char(size: 40)
                ])
            ],

        ]);

        $models->install();
        self::$models = $models;

        self::$connection = $connections['primary'];
        self::$animals = $models['animals'];
        self::$dogs = $models['dogs'];

        for ($i = 0; $i < self::$n; $i++) {
            $properties = [

                'name' => uniqid('', true),
                'date' => gmdate('Y-m-d H:i:s', time() + 60 * rand(1, 3600)),
                'legs' => rand(2, 16),
                'bark_volume' => rand(100, 1000) / 100
            ];

            $key = self::$dogs->save($properties);

            self::$source[$key] = $properties;
        }
    }

//    protected function setUp(): void
//    {
//        $this->connections = new ConnectionCollection([
//
//            'primary' => 'sqlite::memory:'
//
//        ]);
//    }

    public function test_one(): void
    {
        $this->assertInstanceOf(Dog::class, self::$dogs->one);
    }

//    public function test_all()
//    {
//        $all = self::$dogs->all;
//
//        $this->assertIsArray($all);
//        $this->assertCount(self::$n, $all);
//    }
//
//    public function test_order()
//    {
//        $m = self::$animals;
//
//        $q = $m->order('name ASC, legs DESC');
//        $this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY name ASC, legs DESC", (string) $q);
//    }
//
//    public function test_order_by_field()
//    {
//        $m = self::$animals;
//
//        $q = $m->order('id', [ 1, 2, 3 ]);
//        $this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY FIELD(id, '1', '2', '3')", (string) $q);
//
//        $q = $m->order('id', 1, 2, 3);
//        $this->assertEquals("SELECT * FROM `animals` `animal` ORDER BY FIELD(id, '1', '2', '3')", (string) $q);
//    }
//
//    public function test_conditions()
//    {
//        $query = new Query(self::$animals);
//
//        $query->where([ 'name' => 'madonna' ])
//            ->filter_by_legs(2)
//            ->and('YEAR(date) = ?', 1958);
//
//        $this->assertSame([
//
//            "(`name` = ?)",
//            "(`legs` = ?)",
//            "(YEAR(date) = ?)"
//
//        ], $query->conditions);
//
//        $this->assertSame([
//
//            "madonna",
//            2,
//            1958
//
//        ], $query->conditions_args);
//    }
//
//    public function test_join_with_query()
//    {
//        $models = self::$models;
//        $updates = $models['updates'];
//        $subscribers = $models['subscribers'];
//
//        $update_query = $updates
//            ->select('subscriber_id, updated_at, update_hash')
//            ->order('updated_at DESC');
//
//        $subscriber_query = $subscribers
//            ->join($update_query, [ 'on' => 'subscriber_id' ])
//            ->group("`{alias}`.subscriber_id");
//
//        $this->assertEquals(
//            [ "INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM `updates` `update` ORDER BY updated_at DESC) `update` USING(`subscriber_id`)" ],
//            $subscriber_query->joints
//        );
//        $this->assertEquals(
//            "SELECT * FROM `subscribers` `subscriber` INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM `updates` `update` ORDER BY updated_at DESC) `update` USING(`subscriber_id`) GROUP BY `subscriber`.subscriber_id",
//            (string) $subscriber_query
//        );
//    }
//
//    public function test_join_with_query_with_args()
//    {
//        $models = self::$models;
//        $updates = $models['updates'];
//        $subscribers = $models['subscribers'];
//        $now = DateTime::now();
//
//        $update_query = $updates
//            ->select('subscriber_id, updated_at, update_hash')
//            ->where('updated_at < ?', $now)
//            ->order('updated_at DESC');
//
//        $subscriber_query = $subscribers
//            ->join($update_query, [ 'on' => 'subscriber_id' ])
//            ->filter_by_email('person@example.com')
//            ->group("`{alias}`.subscriber_id");
//
//        $this->assertEquals("SELECT * FROM `subscribers` `subscriber` INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM `updates` `update` WHERE (updated_at < ?) ORDER BY updated_at DESC) `update` USING(`subscriber_id`) WHERE (`email` = ?) GROUP BY `subscriber`.subscriber_id", (string) $subscriber_query);
//        $this->assertSame([ $now->utc->as_db ], $subscriber_query->joints_args);
//        $this->assertSame([ 'person@example.com' ], $subscriber_query->conditions_args);
//        $this->assertSame([ $now->utc->as_db, 'person@example.com' ], $subscriber_query->args);
//    }
//
//    public function test_join_with_model()
//    {
//        $models = self::$models;
//        $updates = $models['updates'];
//        $subscribers = $models['subscribers'];
//
//        $this->assertEquals(
//            "SELECT update_id, email FROM `updates` `update` INNER JOIN `subscribers` AS `subscriber` USING(`subscriber_id`)",
//            (string) $updates->select('update_id, email')->join($subscribers)
//        );
//
//        $this->assertEquals(
//            "SELECT update_id, email FROM `updates` `update` INNER JOIN `subscribers` AS `sub` USING(`subscriber_id`)",
//            (string) $updates->select('update_id, email')->join($subscribers, [ 'as' => 'sub' ])
//        );
//
//        $this->assertEquals(
//            "SELECT update_id, email FROM `updates` `update` LEFT JOIN `subscribers` AS `sub` USING(`subscriber_id`)",
//            (string) $updates->select('update_id, email')->join($subscribers, [ 'as' => 'sub', 'mode' => 'LEFT' ])
//        );
//    }
}
