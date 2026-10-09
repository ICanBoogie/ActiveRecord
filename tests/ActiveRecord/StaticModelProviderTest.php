<?php

namespace Test\ICanBoogie\ActiveRecord;

use Exception;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelProvider;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Node;

final class StaticModelProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        StaticModelProvider::reset();
    }

    public function test_set_get_unset(): void
    {
        $factory = fn() => throw new Exception();
        StaticModelProvider::set($factory);

        $actual = StaticModelProvider::get();
        $this->assertSame($actual, $factory);

        StaticModelProvider::reset();

        $actual = StaticModelProvider::get();
        $this->assertNull($actual);
    }

    public function test_model_for_activerecord(): void
    {
        $model = $this->createStub(Model::class);

        $provider = $this->createMock(ModelProvider::class);
        $provider
            // because the model is cached, the provider is called only once.
            ->expects($this->exactly(1))
            ->method('model_for_record')
            ->with(Article::class)
            ->willReturn($model);

        StaticModelProvider::set(static function() use (&$factory_calls, $provider) {
            $factory_calls++;
            return $provider;
        });

        $actual = StaticModelProvider::model_for_record(Article::class);

        $this->assertSame($model, $actual);
        $this->assertEquals(1, $factory_calls);

        // Assert the factory is only once
        $this->assertSame($model, StaticModelProvider::model_for_record(Article::class));
        $this->assertEquals(1, $factory_calls);
    }

    public function test_models_are_cached_per_class(): void
    {
        $article_model = $this->createStub(Model::class);
        $node_model = $this->createStub(Model::class);

        $provider = $this->createMock(ModelProvider::class);
        $provider
            ->expects($this->exactly(2))
            ->method('model_for_record')
            ->willReturnMap([
                [Article::class, $article_model],
                [Node::class, $node_model],
            ]);

        StaticModelProvider::set(static fn() => $provider);

        $this->assertSame($article_model, StaticModelProvider::model_for_record(Article::class));
        $this->assertSame($node_model, StaticModelProvider::model_for_record(Node::class));

        // Cached: the provider is not called again.
        $this->assertSame($article_model, StaticModelProvider::model_for_record(Article::class));
        $this->assertSame($node_model, StaticModelProvider::model_for_record(Node::class));
    }

    public function test_set_resets_the_model_cache(): void
    {
        $model = $this->createStub(Model::class);
        $provider = $this->createMock(ModelProvider::class);
        $provider
            ->expects($this->once())
            ->method('model_for_record')
            ->with(Article::class)
            ->willReturn($model);

        StaticModelProvider::set(static fn() => $provider);
        StaticModelProvider::model_for_record(Article::class);

        // A new factory must invalidate the model cache.
        $other_model = $this->createStub(Model::class);
        $other_provider = $this->createMock(ModelProvider::class);
        $other_provider
            ->expects($this->once())
            ->method('model_for_record')
            ->with(Article::class)
            ->willReturn($other_model);

        StaticModelProvider::set(static fn() => $other_provider);

        $this->assertSame($other_model, StaticModelProvider::model_for_record(Article::class));
    }
}
