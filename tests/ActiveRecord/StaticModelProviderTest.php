<?php

namespace Test\ICanBoogie\ActiveRecord;

use Exception;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelProvider;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;

final class StaticModelProviderTest extends TestCase
{
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
            ->expects($this->exactly(2))
            ->method('model_for_record')
            ->with(Article::class)
            ->willReturn($model);

        StaticModelProvider::set(static function() use (&$n, $provider) {
            $n++;
            return $provider;
        });

        $actual = StaticModelProvider::model_for_record(Article::class);

        $this->assertSame($model, $actual);
        $this->assertEquals(1, $n);

        // Assert the factory is only once
        StaticModelProvider::model_for_record(Article::class);
        $this->assertEquals(1, $n);
    }
}
