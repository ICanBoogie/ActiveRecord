<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\ModelRegistry;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\FailingModel;
use Test\ICanBoogie\Acme\ForeignKey\Author;
use Test\ICanBoogie\Acme\ForeignKey\Book;
use Test\ICanBoogie\Acme\ForeignKey\Loan;
use Test\ICanBoogie\Acme\ForeignKey\Review;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;
use Test\ICanBoogie\RecordingInstallProgress;

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

    public function test_install_reports_progress(): void
    {
        $this->sut->install($progress = new RecordingInstallProgress());

        $this->assertSame([
            [ 'installing', Node::class ],
            [ 'installed', Node::class ],
            [ 'installing', Article::class ],
            [ 'installed', Article::class ],
            [ 'installing', Comment::class ],
            [ 'installed', Comment::class ],
        ], $progress->events);

        $this->sut->install($progress = new RecordingInstallProgress());

        $this->assertSame([
            [ 'already_installed', Node::class ],
            [ 'already_installed', Article::class ],
            [ 'already_installed', Comment::class ],
        ], $progress->events);
    }

    public function test_install_parent_first(): void
    {
        $installer = new ModelInstaller(self::models(fn(ConfigBuilder $config) => $config
            ->add_record(Article::class)
            ->add_record(Node::class)));

        $installer->install($progress = new RecordingInstallProgress());

        $this->assertSame([
            [ 'installing', Node::class ],
            [ 'installed', Node::class ],
            [ 'installing', Article::class ],
            [ 'installed', Article::class ],
        ], $progress->events);
    }

    public function test_install_throws_first_failure_by_default(): void
    {
        $installer = new ModelInstaller(self::models(fn(ConfigBuilder $config) => $config
            ->add_record(Node::class, model_class: FailingModel::class)
            ->add_record(Comment::class)));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIs("Unable to install " . Node::class);

        $installer->install();
    }

    public function test_install_skips_children_of_failed_model(): void
    {
        $models = self::models(fn(ConfigBuilder $config) => $config
            ->add_record(Node::class, model_class: FailingModel::class)
            ->add_record(Article::class)
            ->add_record(Comment::class));

        new ModelInstaller($models)->install($progress = new RecordingInstallProgress());

        $this->assertSame([
            [ 'installing', Node::class ],
            [ 'failed', Node::class, "Unable to install " . Node::class ],
            [ 'skipped', Article::class, Node::class ],
            [ 'installing', Comment::class ],
            [ 'installed', Comment::class ],
        ], $progress->events);
    }

    public function test_install_skips_models_referencing_failed_model(): void
    {
        $models = self::models(fn(ConfigBuilder $config) => $config
            ->add_record(Review::class)
            ->add_record(Loan::class)
            ->add_record(Book::class)
            ->add_record(Author::class, model_class: FailingModel::class));

        new ModelInstaller($models)->install($progress = new RecordingInstallProgress());

        $this->assertSame([
            [ 'installing', Author::class ],
            [ 'failed', Author::class, "Unable to install " . Author::class ],
            [ 'skipped', Book::class, Author::class ],
            [ 'skipped', Review::class, Book::class ],
            [ 'skipped', Loan::class, Book::class ],
        ], $progress->events);
    }

    /**
     * @param callable(ConfigBuilder): ConfigBuilder $add_records
     */
    private static function models(callable $add_records): ModelRegistry
    {
        $config = $add_records(Fixtures::with_main_connection(new ConfigBuilder())->use_attributes())->build();

        return new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);
    }
}
