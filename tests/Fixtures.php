<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\SchemaBuilder;
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
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\Subscriber;
use Test\ICanBoogie\Acme\Update;

final class Fixtures
{
    /**
     * @param string ...$model_ids
     *     Model identifiers.
     */
    public static function only_models(string ...$model_ids): ModelCollection
    {
        $config = self::with_models(
            self::with_main_connection(new ConfigBuilder()),
            $model_ids
        )->build();

        $connections = new ConnectionCollection($config->connections);

        return new ModelCollection($connections, $config->models);
    }

    public static function with_main_connection(ConfigBuilder $builder): ConfigBuilder
    {
        return $builder
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:');
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
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('nid', primary: true)
                        ->add_character('title'),
                ),
                'articles' => $config->add_record(
                    record_class: Article::class,
                    query_class: ArticleQuery::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_character('body')
                        ->add_datetime('date', default: DateTime::CURRENT_TIMESTAMP)
                        ->add_integer('rating', size: Integer::SIZE_TINY, null: true),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many(Comment::class, foreign_key: 'nid')
                        ->has_many(Comment::class, foreign_key: 'nid', as: 'article_comments') // testing 'as'
                ),
                'comments' => $config->add_record(
                    record_class: Comment::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('comment_id', primary: true)
                        ->belongs_to('nid', Article::class)
                        ->add_text('body'),
                ),
                'counts' => $config->add_record(
                    record_class: Count::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('id', primary: true)
                        ->add_character('name')
                        ->add_datetime('date'),
                ),
                #
                # car&drivers
                #
                'drivers' => $config->add_record(
                    record_class: Driver::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('driver_id', primary: true)
                        ->add_character('name')
                ),
                'brands' => $config->add_record(
                    record_class: Brand::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('brand_id', primary: true)
                        ->add_character('name'),
                ),
                'cars' => $config->add_record(
                    record_class: Car::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('car_id', primary: true)
                        ->belongs_to('driver_id', Driver::class)
                        ->belongs_to('brand_id', Brand::class)
                        ->add_character('name'),
                ),
                #
                #
                #
                'subscribers' => $config->add_record(
                    record_class: Subscriber::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('subscriber_id', primary: true)
                        ->add_character('email'),
                ),
                'updates' => $config->add_record(
                    record_class: Update::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('updated_id', primary: true)
                        ->belongs_to('subscriber_id', Subscriber::class)
                        ->add_datetime('updated_at')
                        ->add_character('updated_hash', size: 40, fixed: true),
                ),
                #
                #
                #
                'physicians' => $config->add_record(
                    record_class: Physician::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ph_id', primary: true)
                        ->add_character('name'),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many(Appointment::class, foreign_key: 'physician_id')
                        ->has_many(Patient::class, through: Appointment::class),
                ),
                'appointments' => $config->add_record(
                    record_class: Appointment::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ap_id', primary: true)
                        ->belongs_to('physician_id', Physician::class)
                        ->belongs_to('patient_id', Patient::class)
                        ->add_date('appointment_date'),
                ),
                'patients' => $config->add_record(
                    record_class: Patient::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('pa_id', primary: true)
                        ->add_character('name'),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many(Appointment::class, foreign_key: 'patient_id')
                        ->has_many(Physician::class, foreign_key: 'patient_id', through: Appointment::class),
                ),
                default => throw new LogicException("We don't have that model: $id")
            };
        }

        return $config;
    }

    /**
     * @return array{ ConnectionCollection, ModelCollection }
     */
    public static function connections_and_models(Config $config): array
    {
        return [

            $connections = new ConnectionCollection($config->connections),
            new ModelCollection($connections, $config->models),

        ];
    }
}
