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
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Fixtures;

use function gmdate;
use function rand;
use function time;
use function uniqid;

final class QueryTest extends TestCase
{
    private const N = 10;
    private ModelCollection $models;
    private Model $nodes;
    private Model $articles;

    protected function setUp(): void
    {
        parent::setUp();

        [ , $models ] = Fixtures::only_models([ 'nodes', 'articles', 'subscribers', 'updates' ]);

        $models->install();

        $this->models = $models;
        $this->nodes = $models['nodes'];
        $this->articles = $models['articles'];

        for ($i = 0; $i < self::N; $i++) {
            $properties = [

                'title' => uniqid('', true),
                'body' => uniqid('', true),
                'date' => gmdate('Y-m-d H:i:s', time() + 60 * rand(1, 3600)),
                'rating' => rand(0, 5),

            ];

            $key = $this->articles->save($properties);
        }
    }

    public function test_one(): void
    {
        $this->assertInstanceOf(Article::class, $this->articles->one);
    }

    public function test_all(): void
    {
        $all = $this->articles->all;

        $this->assertIsArray($all);
        $this->assertCount(self::N, $all);
    }

    public function test_order(): void
    {
        $actual = $this->articles->order('title ASC, rating DESC');

        $this->assertEquals(
            "SELECT * FROM `articles` `article` INNER JOIN `nodes` `node` USING(`nid`) ORDER BY title ASC, rating DESC",
            (string)$actual
        );
    }

    public function test_order_expand_minus(): void
    {
        $actual = $this->articles->order('title ASC, -rating');

        $this->assertEquals(
            "SELECT * FROM `articles` `article` INNER JOIN `nodes` `node` USING(`nid`) ORDER BY title ASC, rating DESC",
            (string)$actual
        );

        $actual = $this->articles->order('title ASC, -rating_underscored');

        $this->assertEquals(
            "SELECT * FROM `articles` `article` INNER JOIN `nodes` `node` USING(`nid`) ORDER BY title ASC, rating_underscored DESC",
            (string)$actual
        );
    }

    public function test_order_by_field(): void
    {
        $m = $this->nodes;

        $q = $m->order('nid', [ 1, 2, 3 ]);
        $this->assertEquals("SELECT * FROM `nodes` `node` ORDER BY FIELD(nid, '1', '2', '3')", (string)$q);

        $q = $m->order('nid', 1, 2, 3);
        $this->assertEquals("SELECT * FROM `nodes` `node` ORDER BY FIELD(nid, '1', '2', '3')", (string)$q);
    }

    public function test_conditions(): void
    {
        $query = new Query($this->articles);

        $query->where([ 'title' => 'madonna' ])
            ->filter_by_rating(2)
            ->and('YEAR(date) = ?', 1958);

        $this->assertSame([

            "(`title` = ?)",
            "(`rating` = ?)",
            "(YEAR(date) = ?)"

        ], $query->conditions);

        $this->assertSame([

            "madonna",
            2,
            1958

        ], $query->conditions_args);
    }

    public function test_join_with_query(): void
    {
        $models = $this->models;
        $updates = $models['updates'];
        $subscribers = $models['subscribers'];

        $update_query = $updates
            ->select('subscriber_id, updated_at, update_hash')
            ->order('updated_at DESC');

        $subscriber_query = $subscribers
            ->join($update_query, [ 'on' => 'subscriber_id' ])
            ->group("`{alias}`.subscriber_id");

        $this->assertEquals(
            [ "INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM `updates` `update` ORDER BY updated_at DESC) `update` USING(`subscriber_id`)" ],
            $subscriber_query->joints
        );
        $this->assertEquals(
            "SELECT * FROM `subscribers` `subscriber` INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM `updates` `update` ORDER BY updated_at DESC) `update` USING(`subscriber_id`) GROUP BY `subscriber`.subscriber_id",
            (string)$subscriber_query
        );
    }

    public function test_join_with_query_with_args(): void
    {
        $models = $this->models;
        $updates = $models['updates'];
        $subscribers = $models['subscribers'];
        $now = DateTime::now();

        $update_query = $updates
            ->select('subscriber_id, updated_at, update_hash')
            ->where('updated_at < ?', $now)
            ->order('updated_at DESC');

        $subscriber_query = $subscribers
            ->join($update_query, [ 'on' => 'subscriber_id' ])
            ->filter_by_email('person@example.com')
            ->group("`{alias}`.subscriber_id");

        $this->assertEquals(
            "SELECT * FROM `subscribers` `subscriber` INNER JOIN(SELECT subscriber_id, updated_at, update_hash FROM `updates` `update` WHERE (updated_at < ?) ORDER BY updated_at DESC) `update` USING(`subscriber_id`) WHERE (`email` = ?) GROUP BY `subscriber`.subscriber_id",
            (string)$subscriber_query
        );
        $this->assertSame([ $now->utc->as_db ], $subscriber_query->joints_args);
        $this->assertSame([ 'person@example.com' ], $subscriber_query->conditions_args);
        $this->assertSame([ $now->utc->as_db, 'person@example.com' ], $subscriber_query->args);
    }

    public function test_join_with_model(): void
    {
        $models = $this->models;
        $updates = $models['updates'];
        $subscribers = $models['subscribers'];

        $this->assertEquals(
            "SELECT update_id, email FROM `updates` `update` INNER JOIN `subscribers` AS `subscriber` USING(`subscriber_id`)",
            (string)$updates->select('update_id, email')->join($subscribers)
        );

        $this->assertEquals(
            "SELECT update_id, email FROM `updates` `update` INNER JOIN `subscribers` AS `sub` USING(`subscriber_id`)",
            (string)$updates->select('update_id, email')->join($subscribers, [ 'as' => 'sub' ])
        );

        $this->assertEquals(
            "SELECT update_id, email FROM `updates` `update` LEFT JOIN `subscribers` AS `sub` USING(`subscriber_id`)",
            (string)$updates->select('update_id, email')->join($subscribers, [ 'as' => 'sub', 'mode' => 'LEFT' ])
        );
    }
}
