<?php

namespace ICanBoogie\ActiveRecord;

use ICanBoogie\Acme\CommentModel;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Fixtures;

use function array_keys;

final class ModelCollectionTest extends TestCase
{
    private ConnectionCollection $connections;
    private ModelCollection $models;

    protected function setUp(): void
    {
        [ $this->connections, $this->models ] = Fixtures::only_models([ 'nodes', 'articles', 'comments' ]);
    }

    public function test_get_definitions(): void
    {
        $models = $this->models;
        $this->assertIsArray($models->definitions);
        $this->assertEquals([ 'nodes', 'articles', 'comments' ], array_keys($models->definitions));
    }

    public function test_iterator(): void
    {
        $ids = [];

        foreach ($this->models->model_iterator() as $id => $get) {
            $ids[] = $id;
            $model = $get();

            $this->assertInstanceOf(Model::class, $model);
            $this->assertSame($model, $this->models->model_for_id($id));
        }

        $this->assertEquals([ 'nodes', 'articles', 'comments'], $ids);
    }

    public function test_model_for_id(): void
    {
        $actual = $this->models->model_for_id('articles');

        $this->assertInstanceOf(ArticleModel::class, $actual);
    }

    public function test_model_for_activerecord(): void
    {
        $actual = $this->models->model_for_activerecord(Article::class);

        $this->assertInstanceOf(ArticleModel::class, $actual);
    }

    public function test_get_instances(): void
    {
        $models = $this->models;
        $this->assertIsArray($models->definitions);
        $this->assertEquals([], $models->instances);
        $this->assertInstanceOf(Model::class, $models['nodes']);
        $this->assertInstanceOf(ArticleModel::class, $models['articles']);
        $this->assertInstanceOf(CommentModel::class, $models['comments']);

        foreach ($models->instances as $model) {
            $this->assertInstanceOf(Model::class, $model);
        }

        $this->assertEquals([ 'nodes', 'articles', 'comments' ], array_keys($models->instances));
    }

    public function test_get_connections(): void
    {
        $this->assertSame($this->connections, $this->models->connections);
    }

    public function test_offset_exists(): void
    {
        $models = $this->models;
        $this->assertTrue(isset($models['articles']));
        $this->assertFalse(isset($models['undefined']));
    }

    public function test_offset_set(): void
    {
        $models = $this->models;
        $models['brands'] = [
            Model::ACTIVERECORD_CLASS => Brand::class,
            Model::SCHEMA => new Schema([
                'brand_id' => SchemaColumn::serial(primary: true),
                'name' => SchemaColumn::varchar(),
            ])
        ];

        $this->assertTrue(isset($models['brands']));
        $this->assertInstanceOf(Model::class, $models['brands']);

        try {
            $models['brands'] = [];

            $this->fail("Expected ModelAlreadyInstantiated");
        } catch (ModelAlreadyInstantiated $e) {
            $this->assertEquals('brands', $e->id);
        }
    }

    public function test_should_get_same(): void
    {
        $articles = $this->models['articles'];
        $this->assertSame($articles, $this->models['articles']);
    }

    public function test_offset_get_undefined(): void
    {
        $this->expectException(ModelNotDefined::class);
        $this->models[uniqid()];
    }

    public function test_offset_unset(): void
    {
        $models = $this->models;
        unset($models['comments']);
        $this->assertFalse(isset($models['comments']));
        $this->assertInstanceOf(Model::class, $models['articles']);

        try {
            unset($models['articles']);

            $this->fail("Expected ModelAlreadyInstantiated");
        } catch (ModelAlreadyInstantiated $e) {
            $this->assertEquals('articles', $e->id);
        }
    }

    public function test_install(): void
    {
        $this->assertSame([

            "nodes" => false,
            "articles" => false,
            "comments" => false,

        ], $this->models->is_installed());

        $this->models->install();

        $this->assertSame([

            "nodes" => true,
            "articles" => true,
            "comments" => true,

        ], $this->models->is_installed());

        $this->models->install(); // installing twice shouldn't raise any alarm
    }

    public function test_uninstall(): void
    {
        $this->models->install();
        $this->models->uninstall();

        $this->assertSame([

            "nodes" => false,
            "articles" => false,
            "comments" => false,

        ], $this->models->is_installed());

        $this->models->uninstall(); // uninstalling twice shouldn't raise any alarm
    }

    public function test_class_name(): void
    {
        $this->assertInstanceOf(Model::class, $this->models['nodes']);
        $this->assertInstanceOf(ArticleModel::class, $this->models['articles']);
        $this->assertInstanceOf(CommentModel::class, $this->models['comments']);
    }
}
