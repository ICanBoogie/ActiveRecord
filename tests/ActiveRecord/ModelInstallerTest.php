<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ModelInstaller;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group("db")]
final class ModelInstallerTest extends DbTestCase
{
    private ModelInstaller $sut;

    protected function setUp(): void
    {
        $this->sut = new ModelInstaller(Fixtures::only_models('nodes', 'articles', 'comments'));
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
