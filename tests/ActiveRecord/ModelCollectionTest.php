<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ModelCollection;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\CommentModel;
use Test\ICanBoogie\Acme\NodeModel;
use Test\ICanBoogie\Fixtures;

use function array_keys;

final class ModelCollectionTest extends TestCase
{
    private ConnectionCollection $connections;
    private ModelCollection $sut;

    protected function setUp(): void
    {
        [ $this->connections, $this->sut ] = Fixtures::only_models([ 'nodes', 'articles', 'comments' ]);
    }

    public function test_get_definitions(): void
    {
        $expected = [
            NodeModel::class,
            ArticleModel::class,
            CommentModel::class
        ];

        $actual = array_keys($this->sut->definitions);

        $this->assertEquals($expected, $actual);
    }

    public function test_iterator(): void
    {
        $expected = [
            NodeModel::class,
            ArticleModel::class,
            CommentModel::class
        ];

        $classes = [];

        foreach ($this->sut->model_iterator() as $class => $get) {
            $classes[] = $class;
            $model = $get();

            $this->assertSame($model, $this->sut->model_for_class($class));
        }

        $this->assertEquals($expected, $classes);
    }

    public function test_model_for_class(): void
    {
        $classes = [
            NodeModel::class,
            ArticleModel::class,
            CommentModel::class
        ];

        foreach ($classes as $class) {
            $model = $this->sut->model_for_class($class);

            $this->assertSame($class, $model::class);
        }
    }

    public function test_model_for_class_should_instantiate_once(): void
    {
        $expected = $this->sut->model_for_class(ArticleModel::class);
        $actual = $this->sut->model_for_class(ArticleModel::class);

        $this->assertSame($actual, $expected);
    }

    public function test_model_for_activerecord(): void
    {
        $actual = $this->sut->model_for_activerecord(Article::class);

        $this->assertInstanceOf(ArticleModel::class, $actual);
    }

    public function test_get_instances(): void
    {
        $models = $this->sut;
        $this->assertEmpty($models->instances);

        $nodes = $models->model_for_class(NodeModel::class);
        $articles = $models->model_for_class(ArticleModel::class);
        $comments = $models->model_for_class(CommentModel::class);

        $expected = [
            NodeModel::class => $nodes,
            ArticleModel::class => $articles,
            CommentModel::class => $comments,
        ];

        $actual = $models->instances;

        $this->assertEquals($expected, $actual);
    }

    public function test_get_connections(): void
    {
        $this->assertSame($this->connections, $this->sut->connections);
    }

    public function test_install(): void
    {
        $this->assertSame([

            NodeModel::class => false,
            ArticleModel::class => false,
            CommentModel::class => false,

        ], $this->sut->is_installed());

        $this->sut->install();

        $this->assertSame([

            NodeModel::class => true,
            ArticleModel::class => true,
            CommentModel::class => true,

        ], $this->sut->is_installed());

        $this->sut->install(); // installing twice shouldn't raise any alarm
    }

    public function test_uninstall(): void
    {
        $this->sut->install();
        $this->sut->uninstall();

        $this->assertSame([

            NodeModel::class => false,
            ArticleModel::class => false,
            CommentModel::class => false,

        ], $this->sut->is_installed());

        $this->sut->uninstall(); // uninstalling twice shouldn't raise any alarm
    }
}
