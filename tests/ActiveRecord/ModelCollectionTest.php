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

use ICanBoogie\ActiveRecord\ModelCollectionTest\ArticlesModel;
use ICanBoogie\ActiveRecord\ModelCollectionTest\CommentsModel;
use PHPUnit\Framework\TestCase;

class ModelCollectionTest extends TestCase
{
    private ConnectionCollection $connections;
    private ModelCollection $models;

    protected function setUp(): void
    {
        $this->connections = new ConnectionCollection([

            'primary' => 'sqlite::memory:'

        ]);

        $definitions = [

            'articles' => [
                Model::CLASSNAME => ArticlesModel::class,
                Model::SCHEMA => new Schema([
                    'article_id' => SchemaColumn::serial(primary: true),
                    'title' => SchemaColumn::varchar(),
                ])
            ],

            'comments' => [
                Model::CLASSNAME => CommentsModel::class,
                Model::SCHEMA => new Schema([
                    'comment_id' => SchemaColumn::serial(primary: true),
                    'article_id' => SchemaColumn::foreign(),
                    'body' => SchemaColumn::text(),
                ])
            ],

            'other' => [
                Model::SCHEMA => new Schema([
                    'id' => SchemaColumn::serial(primary: true),
                    'value' => SchemaColumn::int(),
                ])
            ]
        ];

        $this->models = new ModelCollection($this->connections, $definitions);
    }

    public function test_model_for_id(): void
    {
        $actual = $this->models->model_for_id('articles');

        $this->assertInstanceOf(ArticlesModel::class, $actual);
    }

    public function test_get_instances(): void
    {
        $models = $this->models;
        $this->assertIsArray($models->definitions);
        $this->assertEquals([], $models->instances);
        $this->assertInstanceOf(Model::class, $models['articles']);
        $this->assertInstanceOf(Model::class, $models['comments']);

        foreach ($models->instances as $model) {
            $this->assertInstanceOf(Model::class, $model);
        }

        $this->assertEquals([ 'articles', 'comments' ], array_keys($models->instances));
    }

    public function test_get_definitions(): void
    {
        $models = $this->models;
        $this->assertIsArray($models->definitions);
        $this->assertEquals([ 'articles', 'comments', 'other' ], array_keys($models->definitions));
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
        $models['one'] = [
            Model::SCHEMA => new Schema([
                'id' => SchemaColumn::serial(primary: true),
            ])
        ];

        $this->assertTrue(isset($models['one']));
        $this->assertInstanceOf(Model::class, $models['one']);

        try {
            $models['one'] = [];

            $this->fail("Expected ModelAlreadyInstantiated");
        } catch (ModelAlreadyInstantiated $e) {
            $this->assertEquals('one', $e->id);
        }
    }

    public function test_should_get_same(): void
    {
        $articles = $this->models['articles'];
        $this->assertSame($articles, $this->models['articles']);
    }

    public function test_offset_get_undefined(): void
    {
        $this->expectException(\ICanBoogie\ActiveRecord\ModelNotDefined::class);
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

            "articles" => false,
            "comments" => false,
            "other" => false

        ], $this->models->is_installed());

        $this->models->install();

        $this->assertSame([

            "articles" => true,
            "comments" => true,
            "other" => true

        ], $this->models->is_installed());

        $this->models->install(); // installing twice shouldn't raise any alarm
    }

    public function test_uninstall(): void
    {
        $this->models->install();

        $this->models->uninstall();

        $this->assertSame([

            "articles" => false,
            "comments" => false,
            "other" => false

        ], $this->models->is_installed());

        $this->models->uninstall(); // uninstalling twice shouldn't raise any alarm
    }

    public function test_class_name(): void
    {
        $this->assertInstanceOf(ArticlesModel::class, $this->models['articles']);
        $this->assertInstanceOf(CommentsModel::class, $this->models['comments']);
        $this->assertInstanceOf(Model::class, $this->models['other']);
    }
}

namespace ICanBoogie\ActiveRecord\ModelCollectionTest;

use ICanBoogie\ActiveRecord\Model;

class ArticlesModel extends Model
{
}

class CommentsModel extends Model
{
}
