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

class ModelCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConnectionCollection
     */
    private $connections;

    /**
     * @var ModelCollection
     */
    private $models;

    /**
     * @var array
     */
    private $definitions;

    protected function setUp(): void
    {
        $this->connections = new ConnectionCollection([

            'primary' => 'sqlite::memory:'

        ]);

        $this->definitions = [

            'articles' => [

                Model::CLASSNAME => __CLASS__ . '\ArticlesModel',
                Model::SCHEMA => [

                    'article_id' => 'serial',
                    'title' => 'varchar'

                ]
            ],

            'comments' => [

                Model::CLASSNAME => __CLASS__ . '\CommentsModel',
                Model::SCHEMA => [

                    'comment_id' => 'serial',
                    'article_id' => 'foreign',
                    'body' => 'text'

                ]
            ],

            'other' => [

                Model::SCHEMA => [

                    'id' => 'serial',
                    'value' => 'integer'

                ]
            ]
        ];

        $this->models = new ModelCollection($this->connections, $this->definitions);
    }

    public function test_get_instances()
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

    public function test_get_definitions()
    {
        $models = $this->models;
        $this->assertIsArray($models->definitions);
        $this->assertEquals([ 'articles', 'comments', 'other' ], array_keys($models->definitions));
    }

    public function test_get_connections()
    {
        $this->assertSame($this->connections, $this->models->connections);
    }

    public function test_offset_exists()
    {
        $models = $this->models;
        $this->assertTrue(isset($models['articles']));
        $this->assertFalse(isset($models['undefined']));
    }

    public function test_offset_set()
    {
        $models = $this->models;
        $models['one'] = [

            Model::SCHEMA => [

                'id' => 'serial'

            ]
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

    public function test_should_get_same()
    {
        $articles = $this->models['articles'];
        $this->assertSame($articles, $this->models['articles']);
    }

    public function test_offset_get_undefined()
    {
        $this->expectException(\ICanBoogie\ActiveRecord\ModelNotDefined::class);
        $this->models[uniqid()];
    }

    public function test_offset_unset()
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

    public function test_install()
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

    public function test_uninstall()
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

    public function test_class_name()
    {
        $this->assertInstanceOf(\ICanBoogie\ActiveRecord\ModelCollectionTest\ArticlesModel::class, $this->models['articles']);
        $this->assertInstanceOf(\ICanBoogie\ActiveRecord\ModelCollectionTest\CommentsModel::class, $this->models['comments']);
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
