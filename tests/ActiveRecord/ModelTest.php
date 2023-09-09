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

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ActiveRecordCache;
use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\RecordNotFound;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\ScopeNotDefined;
use ICanBoogie\DateTime;
use ICanBoogie\OffsetNotWritable;
use ICanBoogie\PropertyNotWritable;
use ICanBoogie\Prototype\MethodNotDefined;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\CustomQuery;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\NodeModel;
use Test\ICanBoogie\Acme\SampleRecord;
use Test\ICanBoogie\Fixtures;

use function uniqid;

final class ModelTest extends TestCase
{
    private const PREFIX = 'myprefix';

    private ConnectionCollection $connections;
    private ModelCollection $models;
    private Model $model;
    private int $model_records_count;
    private Model $counts_model;

    protected function setUp(): void
    {
        $config = (new ConfigBuilder())
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: 'sqlite::memory:',
                table_name_prefix: self::PREFIX
            );

        [ $this->connections, $this->models ] = Fixtures::connections_and_models(
            Fixtures::with_models($config, [ 'nodes', 'articles', 'comments', 'counts' ])->build()
        );

        $models = $this->models;
        $models->install();

        $nodes = $models['articles'];
        $nodes->save([ 'title' => 'Madonna', 'body' => uniqid(), 'date' => '1958-08-16' ]);
        $nodes->save([ 'title' => 'Lady Gaga', 'body' => uniqid(), 'date' => '1986-03-28' ]);
        $nodes->save([ 'title' => 'Cat Power', 'body' => uniqid(), 'date' => '1972-01-21' ]);

        $counts = $models['counts'];
        $names = explode('|', 'one|two|three|four');

        foreach ($names as $name) {
            $counts->save([ 'name' => $name, 'date' => DateTime::now() ]);
        }

        $this->model = $nodes;
        $this->model_records_count = 3;
        $this->counts_model = $counts;
    }

    /**
     * @dataProvider provide_get_model
     *
     * @param class-string $class
     */
    public function test_get_model(string $id, string $class): void
    {
        $actual = $this->models->model_for_id($id);

        $this->assertInstanceOf($class, $actual);
    }

    /**
     * @return array<array{ string, class-string }>
     */
    public static function provide_get_model(): array
    {
        return [

            [ 'nodes', Model::class ],
            [ 'articles', Model::class ],
            [ 'comments', Model::class ],
            [ 'counts', Model::class ],

        ];
    }

    public function test_call_undefined_method(): void
    {
        $this->expectException(MethodNotDefined::class);
        $this->models['nodes']->undefined_method();
    }

    public function test_should_instantiate_model(): void
    {
        $models = $this->models;
        $model = $models['nodes'];

        $this->assertSame($models, $model->models);
        $this->assertSame($this->connections['primary'], $model->connection);
        $this->assertSame('nodes', $model->id);
        $this->assertSame(self::PREFIX . '_' . 'nodes', $model->name);
        $this->assertSame('nodes', $model->unprefixed_name);
    }

    public function test_get_parent(): void
    {
        $models = $this->models;
        $this->assertSame($models['nodes'], $models['articles']->parent);
    }

    public function test_should_default_id_from_name(): void
    {
        [ $connections, $models ] = Fixtures::only_models([]);

        $connection = $connections->connection_for_id(Config::DEFAULT_CONNECTION_ID);

        $model = new class (
            $connection,
            $models,
            new Config\ModelDefinition(
                'nodes',
                connection: 'primary',
                schema: (new SchemaBuilder())
                    ->add_serial('id', primary: true)
                    ->build(),
                model_class: NodeModel::class,
            )
        ) extends Model {
            protected static string $activerecord_class = SampleRecord::class; // @phpstan-ignore-line
        };

        $this->assertEquals('nodes', $model->id);
    }

    public function test_should_throw_not_found_when_record_does_not_exists(): void
    {
        $id = rand();

        try {
            $this->models['nodes']->find($id);
            $this->fail("Expected RecordNotFound");
        } catch (RecordNotFound $e) {
            $this->assertSame([ $id => null ], $e->records);
        }
    }

    public function test_should_throw_not_found_when_one_record_does_not_exists(): void
    {
        $id = rand();
        $model = $this->models['nodes'];
        $model->save([ 'title' => uniqid() ]);
        $model->save([ 'title' => uniqid() ]);

        try {
            $model->find(1, 2, $id);
            $this->fail("Expected RecordNotFound");
        } catch (RecordNotFound $e) {
            $records = $e->records;
            $message = $e->getMessage();

            $this->assertStringContainsString((string)$id, $message);

            $this->assertInstanceOf(ActiveRecord::class, $records[1]);
            $this->assertInstanceOf(ActiveRecord::class, $records[2]);
            $this->assertNull($records[$id]);
        }
    }

    public function test_should_throw_not_found_when_all_record_do_not_exists(): void
    {
        $id1 = rand();
        $id2 = rand();
        $id3 = rand();

        $model = $this->models['nodes'];

        try {
            $model->find($id1, $id2, $id3);
            $this->fail("Expected RecordNotFound");
        } catch (RecordNotFound $e) {
            $records = $e->records;
            $message = $e->getMessage();

            $this->assertStringContainsString((string)$id1, $message);
            $this->assertStringContainsString((string)$id2, $message);
            $this->assertStringContainsString((string)$id2, $message);

            $this->assertNull($records[$id1]);
            $this->assertNull($records[$id2]);
            $this->assertNull($records[$id3]);
        }
    }

    public function test_find_one(): void
    {
        $model = $this->models['articles'];
        $id = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $this->assertNotEmpty($id);

        $record = $model->find($id);
        $this->assertInstanceOf(Article::class, $record);
        $this->assertSame($record, $model->find($id));
    }

    public function test_find_many(): void
    {
        $model = $this->models['articles'];
        $id1 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $id2 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $id3 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
        $this->assertNotEmpty($id3);

        $records = $model->find($id1, $id2, $id3);

        $this->assertIsArray($records);
        $this->assertInstanceOf(Article::class, $records[$id1]);
        $this->assertInstanceOf(Article::class, $records[$id2]);
        $this->assertInstanceOf(Article::class, $records[$id3]);

        $records2 = $model->find($id1, $id2, $id3);
        $this->assertSame($records[$id1], $records2[$id1]);
        $this->assertSame($records[$id2], $records2[$id2]);
        $this->assertSame($records[$id3], $records2[$id3]);
    }

    public function test_find_many_with_an_array(): void
    {
        $model = $this->models['articles'];
        $id1 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $id2 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $id3 = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
        $this->assertNotEmpty($id3);

        $records = $model->find([ $id1, $id2, $id3 ]);

        $this->assertIsArray($records);
        $this->assertInstanceOf(Article::class, $records[$id1]);
        $this->assertInstanceOf(Article::class, $records[$id2]);
        $this->assertInstanceOf(Article::class, $records[$id3]);

        $records2 = $model->find([ $id1, $id2, $id3 ]);
        $this->assertSame($records[$id1], $records2[$id1]);
        $this->assertSame($records[$id2], $records2[$id2]);
        $this->assertSame($records[$id3], $records2[$id3]);
    }

    public function test_offsets(): void
    {
        $model = $this->models['nodes'];
        $this->assertFalse(isset($model[uniqid()]));
        $id = $model->save([ 'title' => uniqid() ]);
        $this->assertTrue(isset($model[$id]));
        unset($model[$id]);
        $this->assertFalse(isset($model[$id]));

        try {
            /** @phpstan-ignore-next-line */
            $model[$id] = null;
            $this->fail("Expected OffsetNotWritable");
        } catch (OffsetNotWritable) {
        }
    }

    public function test_new_record(): void
    {
        $model = $this->models['articles'];
        $title = 'Title ' . uniqid();
        $record = $model->new([ 'title' => $title ]);

        $this->assertInstanceOf(Article::class, $record);
        $this->assertSame($title, $record->title);
        $this->assertSame($model, $record->model);
    }

    /**
     * @dataProvider provide_test_readonly_properties
     */
    public function test_readonly_properties(string $property): void
    {
        $this->expectException(PropertyNotWritable::class);
        $this->model->$property = null;
    }

    /**
     * @return array<array{ string }>
     */
    public static function provide_test_readonly_properties(): array
    {
        $properties = 'exists count all one';
        return array_map(function ($v) {
            return (array)$v;
        }, explode(' ', $properties));
    }

    public function test_get_exists(): void
    {
        $this->assertTrue($this->model->exists);
    }

    public function test_get_count(): void
    {
        $this->assertEquals($this->model_records_count, $this->model->count);
    }

    public function test_get_all(): void
    {
        $all = $this->model->all;
        $this->assertIsArray($all);
        $this->assertCount($this->model_records_count, $all);
        $this->assertContainsOnlyInstancesOf(ActiveRecord::class, $all);
    }

    public function test_get_one(): void
    {
        $one = $this->model->one;
        $this->assertInstanceOf(ActiveRecord::class, $one);
    }

    /**
     * @dataProvider provide_test_initiate_query
     *
     * @param string[] $args
     */
    public function test_initiate_query(string $method, array $args): void
    {
        $this->assertInstanceOf(Query::class, $this->model->$method(...$args));
    }

    /**
     * @return array<array{ string, array{ string } }>
     */
    public static function provide_test_initiate_query(): array
    {
        return [

            [ 'select', [ 'id, name' ] ],
            [ 'join', [ 'JOIN some_other_table' ] ],
            [ 'where', [ '1=1' ] ],
            [ 'group', [ 'name' ] ],
            [ 'order', [ 'name' ] ],
            [ 'limit', [ '12' ] ],
            [ 'offset', [ '12' ] ]

        ];
    }

    public function test_has_scope(): void
    {
        $model = $this->models['articles'];

        $this->assertTrue($model->has_scope('ordered'));
        $this->assertFalse($model->has_scope(uniqid()));
    }

    public function test_scope_as_property(): void
    {
        $a = $this->model;
        $q = $a->ordered;
        $this->assertInstanceOf(Query::class, $q);

        $record = $q->one;
        $this->assertInstanceOf(ActiveRecord::class, $record);
        $this->assertEquals('Lady Gaga', $record->title);
    }

    public function test_scope_as_method(): void
    {
        $a = $this->model;
        $q = $a->ordered(1);
        $this->assertInstanceOf(Query::class, $q);

        $record = $q->one;
        $this->assertInstanceOf(ActiveRecord::class, $record);
        $this->assertEquals('Madonna', $record->title);
    }

    public function test_scope_not_defined(): void
    {
        $this->expectException(ScopeNotDefined::class);
        $this->model->scope('undefined' . uniqid());
    }

    public function test_scope_not_defined_from_query(): void
    {
        $this->expectException(ScopeNotDefined::class);
        $this->model->ordered->undefined_scope();
    }

    /*
     * Record existence
     */

    /**
     * `exists()` must return `true` when a record or all the records of a subset exist.
     */
    public function test_exists_true(): void
    {
        $m = $this->counts_model;
        $this->assertTrue($m->exists(1));
        $this->assertTrue($m->exists(1, 2, 3));
        $this->assertTrue($m->exists([ 1, 2, 3 ]));
    }

    /**
     * `exists()` must return `false` when a record or all the records of a subset don't exist.
     */
    public function test_exists_false(): void
    {
        $m = $this->counts_model;
        $u = rand(999, 9999);

        $this->assertFalse($m->exists($u));
        $this->assertFalse($m->exists($u + 1, $u + 2, $u + 3));
        $this->assertFalse($m->exists([ $u + 1, $u + 2, $u + 3 ]));
    }

    /**
     * `exists()` must return an array when some records of a subset don't exist.
     */
    public function test_exists_mixed(): void
    {
        $m = $this->counts_model;
        $u = rand(999, 9999);
        $a = [ 1 => true, $u => false, 3 => true ];

        $this->assertEquals($a, $m->exists(1, $u, 3));
        $this->assertEquals($a, $m->exists([ 1, $u, 3 ]));
    }

    public function test_exists_condition(): void
    {
        $this->assertTrue($this->counts_model->filter_by_name('one')->exists);
        $this->assertFalse($this->counts_model->filter_by_name('one ' . uniqid())->exists);
    }

    public function test_cache_should_be_revoked_on_save(): void
    {
        $name1 = uniqid();
        $name2 = uniqid();

        $model = $this->counts_model;
        $id = $model->save([ 'name' => $name1, 'date' => DateTime::now() ]);
        $record = $model[$id];
        $model->save([ 'name' => $name2 ], $id);
        $record_now = $model[$id];

        $this->assertEquals($name1, $record->name);
        $this->assertEquals($name2, $record_now->name);
        $this->assertNotSame($record, $record_now);
    }

    /**
     * @dataProvider provide_test_querying
     *
     * @param callable $callback
     * @param string $expected
     */
    public function test_querying($callback, $expected): void
    {
        $this->assertSame($expected, (string)$callback($this->model));
    }

    /**
     * @return array[]
     */
    public static function provide_test_querying(): array
    {
        $p = self::PREFIX;
        $l = Query::LIMIT_MAX;

        return [

            [
                function (Model $m) {
                    return $m->select('nid, UPPER(name)');
                },
                <<<EOT
SELECT nid, UPPER(name) FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`)
EOT
            ],

            [
                function (Model $m) {
                    return $m->join(expression: 'INNER JOIN other USING(nid)');
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) INNER JOIN other USING(nid)
EOT
            ],

            [
                function (Model $m) {
                    return $m->where([ 'nid' => 1, 'name' => 'madonna' ]);
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) WHERE (`nid` = ? AND `name` = ?)
EOT
            ],

            [
                function (Model $m) {
                    return $m->group('name');
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) GROUP BY name
EOT
            ],

            [
                function (Model $m) {
                    return $m->order('nid');
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) ORDER BY nid
EOT
            ],

            [
                function (Model $m) {
                    return $m->order('nid', 1, 2, 3);
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) ORDER BY FIELD(nid, '1', '2', '3')
EOT
            ],

            [
                function (Model $m) {
                    return $m->limit(5);
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) LIMIT 5
EOT
            ],

            [
                function (Model $m) {
                    return $m->limit(5, 10);
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) LIMIT 5, 10
EOT
            ],

            [
                function (Model $m) {
                    return $m->offset(5);
                },
                <<<EOT
SELECT * FROM `{$p}_articles` `article` INNER JOIN `{$p}_nodes` `node` USING(`nid`) LIMIT 5, $l
EOT
            ]
        ];
    }

    public function test_activerecord_cache(): void
    {
        $model_id = 't' . uniqid();

        $models = new ModelCollection($this->connections, [
            $model_id => new Config\ModelDefinition(
                id: $model_id,
                connection: Config::DEFAULT_CONNECTION_ID,
                schema: (new SchemaBuilder())
                    ->add_serial('nid', primary: true)
                    ->add_character('title')
                    ->build(),
                model_class: NodeModel::class,
            )
        ]);

        $models->install();
        $model = $models[$model_id];

        foreach ([ 'one', 'two', 'three', 'four' ] as $value) {
            $model->save([ 'title' => $value ]);
        }

        $activerecord_cache = $model->activerecord_cache;

        $this->assertInstanceOf(ActiveRecordCache::class, $activerecord_cache);

        for ($i = 1; $i < 5; $i++) {
            $records[$i] = $model[$i];
        }

        for ($i = 1; $i < 5; $i++) {
            $this->assertSame($records[$i], $activerecord_cache->retrieve($i));
            $this->assertSame($records[$i], $model[$i]);
        }

        $activerecord_cache->clear();

        for ($i = 1; $i < 5; $i++) {
            $this->assertNull($activerecord_cache->retrieve($i));
            $this->assertNotSame($records[$i], $model[$i]);
        }

        #
        # A deleted record must not be available in the cache.
        #

        $records[1]->delete();
        $this->assertNull($activerecord_cache->retrieve(1));
        $this->expectException(RecordNotFound::class);
        $model[1];
    }

    public function test_custom_query(): void
    {
        $model = new class(
            $this->connections['primary'],
            $this->models,
            new ModelDefinition(
                id: uniqid(),
                connection: Config::DEFAULT_CONNECTION_ID,
                schema: (new SchemaBuilder())
                    ->add_serial('id', primary: true)
                    ->build(),
                model_class: NodeModel::class,
                query_class: CustomQuery::class,
            )
        ) extends Model {
            protected static string $activerecord_class = SampleRecord::class; // @phpstan-ignore-line
        };

        $this->assertInstanceOf(CustomQuery::class, $query1 = $model->where('1 = 1'));
        $this->assertInstanceOf(CustomQuery::class, $query2 = $model->query());
        $this->assertNotSame($query1, $query2);
    }

    public function test_query(): void
    {
        $model = $this->model;

        $this->assertInstanceOf(Query::class, $query1 = $model->query());
        $this->assertInstanceOf(Query::class, $query2 = $model->query("1 = 1"));
        $this->assertSame(
            'SELECT * FROM `myprefix_articles` `article`' .
            ' INNER JOIN `myprefix_nodes` `node` USING(`nid`) WHERE (1 = 1)',
            (string)$query2
        );
    }
}
