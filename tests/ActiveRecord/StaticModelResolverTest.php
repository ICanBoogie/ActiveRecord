<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelProvider;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;

final class StaticModelResolverTest extends TestCase
{
    public function test_defined(): void
    {
        $actual = StaticModelProvider::defined();

        $this->assertNotNull($actual);
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

        StaticModelProvider::define(fn() => $resolver);

        $actual = StaticModelProvider::model_for_record(Article::class);

        $this->assertSame($model, $actual);
    }
}
