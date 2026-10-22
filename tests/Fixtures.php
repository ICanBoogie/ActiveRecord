<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\ModelRegistry;
use LogicException;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleQuery;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Count;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\Acme\HasOne\Badge;
use Test\ICanBoogie\Acme\HasOne\Desk;
use Test\ICanBoogie\Acme\HasOne\DeskAssignment;
use Test\ICanBoogie\Acme\HasOne\Employee;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\Subscriber;
use Test\ICanBoogie\Acme\Update;

use function getenv;

final class Fixtures
{
    /**
     * The DSN of the connection used by the tests.
     *
     * It can be configured with the `ACTIVERECORD_DSN` environment variable, and defaults to
     * `sqlite::memory:`.
     *
     * @phpstan-return non-empty-string|null
     */
    public static function dsn(): string
    {
        $dsn = getenv('ACTIVERECORD_DSN');

        return ($dsn === false || $dsn === '') ? 'sqlite::memory:' : $dsn;
    }

    /**
     * The username of the connection used by the tests, configured with the
     * `ACTIVERECORD_USERNAME` environment variable.
     *
     * @phpstan-return non-empty-string|null
     */
    public static function username(): ?string
    {
        $username = getenv('ACTIVERECORD_USERNAME');

        return ($username === false || $username === '') ? null : $username;
    }

    /**
     * The password of the connection used by the tests, configured with the
     * `ACTIVERECORD_PASSWORD` environment variable.
     *
     * @phpstan-return non-empty-string|null
     */
    public static function password(): ?string
    {
        $password = getenv('ACTIVERECORD_PASSWORD');

        return ($password === false || $password === '') ? null : $password;
    }

    /**
     * @param string ...$model_ids
     *     Model identifiers.
     */
    public static function only_models(string ...$model_ids): ModelRegistry
    {
        $config = self::with_models(
            self::with_main_connection(new ConfigBuilder()),
            $model_ids
        )->build();

        $connections = new ConnectionRegistry($config->connections);

        return new ModelRegistry($connections, $config->models);
    }

    public static function with_main_connection(ConfigBuilder $builder): ConfigBuilder
    {
        return $builder->add_connection(
            Config::DEFAULT_CONNECTION_ID,
            self::dsn(),
            username: self::username(),
            password: self::password(),
        );
    }

    /**
     * @param string[] $model_ids
     */
    public static function with_models(ConfigBuilder $config, array $model_ids): ConfigBuilder
    {
        foreach ($model_ids as $id) {
            $_ = match ($id) {
                'nodes' => $config->add_record(
                    record_class: Node::class,
                ),
                'articles' => $config->add_record(
                    record_class: Article::class,
                    query_class: ArticleQuery::class,
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many(Comment::class, foreign_key: 'nid')
                        ->has_many(Comment::class, foreign_key: 'nid', as: 'article_comments') // testing 'as'
                ),
                'comments' => $config->add_record(
                    record_class: Comment::class,
                ),
                'counts' => $config->add_record(
                    record_class: Count::class,
                ),
                #
                # car&drivers
                #
                'drivers' => $config->add_record(
                    record_class: Driver::class,
                ),
                'brands' => $config->add_record(
                    record_class: Brand::class,
                ),
                'cars' => $config->add_record(
                    record_class: Car::class,
                ),
                #
                #
                #
                'subscribers' => $config->add_record(
                    record_class: Subscriber::class,
                ),
                'updates' => $config->add_record(
                    record_class: Update::class,
                ),
                #
                #
                #
                'physicians' => $config->add_record(
                    record_class: Physician::class,
                ),
                'appointments' => $config->add_record(
                    record_class: Appointment::class,
                ),
                'patients' => $config->add_record(
                    record_class: Patient::class,
                ),
                #
                # has one
                #
                'employees' => $config->add_record(
                    record_class: Employee::class,
                ),
                'badges' => $config->add_record(
                    record_class: Badge::class,
                ),
                'desks' => $config->add_record(
                    record_class: Desk::class,
                ),
                'desk_assignments' => $config->add_record(
                    record_class: DeskAssignment::class,
                ),
                default => throw new LogicException("We don't have that model: $id")
            };
        }

        return $config;
    }

    /**
     * @return array{ ConnectionRegistry, ModelRegistry }
     */
    public static function connections_and_models(Config $config): array
    {
        return [

            $connections = new ConnectionRegistry($config->connections),
            new ModelRegistry($connections, $config->models),

        ];
    }
}
