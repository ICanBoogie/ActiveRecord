<?php

namespace ICanBoogie\ActiveRecord;

use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;

final class StaticModelResolverTest extends TestCase
{
    public function test_defined(): void
    {
        $actual = StaticModelResolver::defined();

        $this->assertNull($actual);
    }

    public function test_model_for_activerecord(): void
    {
        $model = $this->getMockBuilder(Model::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolver = $this->createMock(ModelResolver::class);
        $resolver
            ->method('model_for_activerecord')
            ->with(Article::class)
            ->willReturn($model);

        StaticModelResolver::define(fn() => $resolver);

        $actual = StaticModelResolver::model_for_activerecord(Article::class);

        $this->assertSame($model, $actual);
    }
}
