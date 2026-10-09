<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ModelRegistry;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

use function array_keys;

#[Group("db")]
final class ModelRegistryTest extends DbTestCase
{
    private ModelRegistry $sut;

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

        foreach ($this->sut->model_iterator() as $record_class => $accessor) {
            $this->assertFalse($accessor->instantiated);
            $classes[] = $record_class;
            $model = $accessor->get();

            $this->assertSame($model, $this->sut->model_for_record($record_class));
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

    public function test_fail_on_invalid_model(): void
    {
        $this->expectException(LogicException::class);
        // @phpstan-ignore-next-line // bad class on purpose
        $this->sut->model_for_record(self::class);
    }
}
