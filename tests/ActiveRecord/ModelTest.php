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
use ICanBoogie\ActiveRecord\Config\TableDefinition;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\RecordNotFound;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\DateTime;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Count;
use Test\ICanBoogie\Acme\CustomQuery;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\SampleRecord;
use Test\ICanBoogie\Fixtures;

use function uniqid;

final class ModelTest extends TestCase
{
    private const PREFIX = 'myprefix';

    private ConnectionCollection $connections;
    private ModelCollection $models;
    private Model $nodes;
    private Model $articles;
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

        $articles = $models->model_for_record(Article::class);
        $articles->save([ 'title' => 'Madonna', 'body' => uniqid(), 'date' => '1958-08-16' ]);
        $articles->save([ 'title' => 'Lady Gaga', 'body' => uniqid(), 'date' => '1986-03-28' ]);
        $articles->save([ 'title' => 'Cat Power', 'body' => uniqid(), 'date' => '1972-01-21' ]);

        $counts = $models->model_for_record(Count::class);
        $names = explode('|', 'one|two|three|four');

        foreach ($names as $name) {
            $counts->save([ 'name' => $name, 'date' => DateTime::now() ]);
        }

        $this->articles = $articles;
        $this->nodes = $models->model_for_record(Node::class);
        $this->counts_model = $counts;
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

    public function test_should_instantiate_model(): void
    {
        $models = $this->models;
        $model = $this->nodes;

        $this->assertSame($models, $model->models);
        $this->assertSame($this->connections->connection_for_id('primary'), $model->connection);
        $this->assertSame(self::PREFIX . '_' . 'nodes', $model->name);
        $this->assertSame('nodes', $model->unprefixed_name);
    }

    public function test_get_parent(): void
    {
        $this->assertSame($this->nodes, $this->articles->parent);
    }

    public function test_should_throw_not_found_when_record_does_not_exists(): void
    {
        $id = rand();

        try {
            $this->nodes->find($id);
            $this->fail("Expected RecordNotFound");
        } catch (RecordNotFound $e) {
            $this->assertSame([ $id => null ], $e->records);
        }
    }

    public function test_should_throw_not_found_when_one_record_does_not_exists(): void
    {
        $id = rand();
        $model = $this->nodes;
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

        $model = $this->nodes;

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
        $model = $this->articles;
        $id = $model->save([ 'title' => uniqid(), 'body' => uniqid(), 'date' => DateTime::now() ]);
        $this->assertNotEmpty($id);

        $record = $model->find($id);
        $this->assertInstanceOf(Article::class, $record);
        $this->assertSame($record, $model->find($id));
    }

    public function test_find_many(): void
    {
        $model = $this->articles;
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
        $model = $this->articles;
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

    public function test_new_record(): void
    {
        $model = $this->articles;
        $title = 'Title ' . uniqid();
        $record = $model->new([ 'title' => $title ]);

        $this->assertInstanceOf(Article::class, $record);
        $this->assertSame($title, $record->title);
        $this->assertSame($model, $record->model);
    }

    public function test_cache_should_be_revoked_on_save(): void
    {
        $name1 = uniqid();
        $name2 = uniqid();

        $model = $this->counts_model;
        $id = $model->save([ 'name' => $name1, 'date' => DateTime::now() ]);
        $record = $model->find($id);
        $model->save([ 'name' => $name2 ], $id);
        $record_now = $model->find($id);

        $this->assertEquals($name1, $record->name);
        $this->assertEquals($name2, $record_now->name);
        $this->assertNotSame($record, $record_now);
    }

    public function test_activerecord_cache(): void
    {
        $name = 't' . uniqid();

        $models = new ModelCollection($this->connections, [
            Node::class => new Config\ModelDefinition(
                table: new Config\TableDefinition(
                    name: $name,
                    schema: (new SchemaBuilder())
                        ->add_serial('nid', primary: true)
                        ->add_character('title')
                        ->build(),
                ),
                model_class: Model::class,
                activerecord_class: Node::class,
                query_class: Query::class,
                connection: Config::DEFAULT_CONNECTION_ID,
            )
        ]);

        $models->install();
        $model = $models->model_for_record(Node::class);

        foreach ([ 'one', 'two', 'three', 'four' ] as $value) {
            $model->save([ 'title' => $value ]);
        }

        $activerecord_cache = $model->activerecord_cache;

        $this->assertInstanceOf(ActiveRecordCache::class, $activerecord_cache);

        for ($i = 1; $i < 5; $i++) {
            $records[$i] = $model->find($i);
        }

        for ($i = 1; $i < 5; $i++) {
            $this->assertSame($records[$i], $activerecord_cache->retrieve($i));
            $this->assertSame($records[$i], $model->find($i));
        }

        $activerecord_cache->clear();

        for ($i = 1; $i < 5; $i++) {
            $this->assertNull($activerecord_cache->retrieve($i));
            $this->assertNotSame($records[$i], $model->find($i));
        }

        #
        # A deleted record must not be available in the cache.
        #

        $records[1]->delete();
        $this->assertNull($activerecord_cache->retrieve(1));
        $this->expectException(RecordNotFound::class);
        $model->find(1);
    }


    public function test_query(): void
    {
        $actual = $this->articles->query();

        $this->assertInstanceOf(Query::class, $actual);
        $this->assertNotSame($actual, $this->articles->query());
    }

    public function test_where(): void
    {
        $actual = $this->articles->where();

        $this->assertInstanceOf(Query::class, $actual);
        $this->assertNotSame($actual, $this->articles->where());
    }

    public function test_custom_query(): void
    {
        $id = uniqid();
        $model = new class(
            $this->connections->connection_for_id('primary'),
            $this->models,
            new ModelDefinition(
                table: new TableDefinition(
                    name: $id,
                    schema: (new SchemaBuilder())
                        ->add_serial('id', primary: true)
                        ->build()
                ),
                model_class: Model::class,
                activerecord_class: SampleRecord::class,
                query_class: CustomQuery::class,
                connection: Config::DEFAULT_CONNECTION_ID,
            )
        ) extends Model {
        };

        $this->assertInstanceOf(CustomQuery::class, $query1 = $model->where('1 = 1'));
        $this->assertInstanceOf(CustomQuery::class, $query2 = $model->query());
        $this->assertNotSame($query1, $query2);
    }
}
