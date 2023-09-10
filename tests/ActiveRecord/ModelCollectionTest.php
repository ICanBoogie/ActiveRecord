<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ModelCollection;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Fixtures;

use function array_keys;

final class ModelCollectionTest extends TestCase
{
    private ModelCollection $sut;

    protected function setUp(): void
    {
        $this->sut = Fixtures::only_models('nodes', 'articles', 'comments');
    }

    public function test_get_definitions(): void
    {
        $expected = [
            Node::class,
            Article::class,
            Comment::class
        ];

        $actual = array_keys($this->sut->definitions);

        $this->assertEquals($expected, $actual);
    }

    public function test_iterator(): void
    {
        $expected = [
            Node::class,
            Article::class,
            Comment::class
        ];

        $classes = [];

        foreach ($this->sut->model_iterator() as $class => $get) {
            $classes[] = $class;
            $model = $get();

            $this->assertSame($model, $this->sut->model_for_record($class));
        }

        $this->assertEquals($expected, $classes);
    }

    public function test_model_for_class(): void
    {
        $classes = [
            Node::class,
            Article::class,
            Comment::class
        ];

        foreach ($classes as $class) {
            $model = $this->sut->model_for_record($class);

            $this->assertSame($class, $model->activerecord_class);
        }
    }

    public function test_model_for_class_should_instantiate_once(): void
    {
        $expected = $this->sut->model_for_record(Article::class);
        $actual = $this->sut->model_for_record(Article::class);

        $this->assertSame($actual, $expected);
    }

    public function test_get_instances(): void
    {
        $models = $this->sut;
        $this->assertEmpty($models->instances);

        $nodes = $models->model_for_record(Node::class);
        $articles = $models->model_for_record(Article::class);
        $comments = $models->model_for_record(Comment::class);

        $expected = [
            Node::class => $nodes,
            Article::class => $articles,
            Comment::class => $comments,
        ];

        $actual = $models->instances;

        $this->assertEquals($expected, $actual);
    }

    public function test_install(): void
    {
        $this->assertSame([

            Node::class => false,
            Article::class => false,
            Comment::class => false,

        ], $this->sut->is_installed());

        $this->sut->install();

        $this->assertSame([

            Node::class => true,
            Article::class => true,
            Comment::class => true,

        ], $this->sut->is_installed());

        $this->sut->install(); // installing twice shouldn't raise any alarm
    }

    public function test_uninstall(): void
    {
        $this->sut->install();
        $this->sut->uninstall();

        $this->assertSame([

            Node::class => false,
            Article::class => false,
            Comment::class => false,

        ], $this->sut->is_installed());

        $this->sut->uninstall(); // uninstalling twice shouldn't raise any alarm
    }
}
