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

        StaticModelProvider::unset();

        $actual = StaticModelProvider::get();
        $this->assertNull($actual);
    }

    public function test_model_for_activerecord(): void
    {
        $model = $this->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolver = $this->createMock(ModelProvider::class);
        $resolver
            ->method('model_for_record')
            ->with(Article::class)
            ->willReturn($model);

        StaticModelProvider::set(static function() use (&$n, $resolver) {
            $n++;
            return $resolver;
        });

        $actual = StaticModelProvider::model_for_record(Article::class);

        $this->assertSame($model, $actual);
        $this->assertEquals(1, $n);

        // Assert the factory is only once
        StaticModelProvider::model_for_record(Article::class);
        $this->assertEquals(1, $n);
    }
}
