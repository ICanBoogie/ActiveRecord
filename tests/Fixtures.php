<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use LogicException;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\CommentModel;
use Test\ICanBoogie\Acme\Count;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\Subscriber;

final class Fixtures
{
    /**
     * @param string[] $model_ids
     *     An array of model identifiers.
     *
     * @return array{ ConnectionCollection, ModelCollection }
     */
    public static function only_models(array $model_ids): array
    {
        $config = self::with_models(
            self::with_main_connection(new ConfigBuilder()),
            $model_ids
        )->build();

        return [

            $connections = new ConnectionCollection($config->connections),
            new ModelCollection($connections, $config->models),

        ];
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
                'nodes' => $config->add_model(
                    id: 'nodes',
                    activerecord_class: Node::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('nid', primary: true)
                        ->add_varchar('title'),
                ),
                'articles' => $config->add_model(
                    id: 'articles',
                    activerecord_class: Article::class,
                    model_class: ArticleModel::class,
                    extends: 'nodes',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_varchar('body')
                        ->add_datetime('date', default: $schema::CURRENT_TIMESTAMP)
                        ->add_integer('rating', size: $schema::SIZE_TINY, null: true),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many('comments', foreign_key: 'nid')
                ),
                'comments' => $config->add_model(
                    id: 'comments',
                    activerecord_class: Comment::class,
                    model_class: CommentModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('comment_id', primary: true)
                        ->add_foreign('nid')
                        ->add_text('body'),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->belongs_to('articles', local_key: 'nid')
                ),
                'counts' => $config->add_model(
                    id: 'counts',
                    activerecord_class: Count::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('id', primary: true)
                        ->add_varchar('name')
                        ->add_datetime('date'),
                ),
                #
                # car&drivers
                #
                'drivers' => $config->add_model(
                    id: 'drivers',
                    activerecord_class: Driver::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('driver_id', primary: true)
                        ->add_varchar('name')
                ),
                'brands' => $config->add_model(
                    id: 'brands',
                    activerecord_class: Brand::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('brand_id', primary: true)
                        ->add_varchar('name'),
                ),
                'cars' => $config->add_model(
                    id: 'cars',
                    activerecord_class: Car::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('car_id', primary: true)
                        ->add_foreign('driver_id')
                        ->add_foreign('brand_id')
                        ->add_varchar('name'),
                ),
                #
                #
                #
                'subscribers' => $config->add_model(
                    id: 'subscribers',
                    activerecord_class: Subscriber::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('subscriber_id', primary: true)
                        ->add_varchar('email'),
                ),
                'updates' => $config->add_model(
                    id: 'updates',
                    activerecord_class: Subscriber::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('updated_id', primary: true)
                        ->add_foreign('subscriber_id')
                        ->add_datetime('updated_at')
                        ->add_char('updated_hash', size: 40),
                ),
                #
                #
                #
                'physicians' => $config->add_model(
                    id: 'physicians',
                    activerecord_class: Physician::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ph_id', primary: true)
                        ->add_varchar('name'),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many('appointments', foreign_key: 'physician_id')
                        ->has_many('patients', through: 'appointments'),
                ),
                'appointments' => $config->add_model(
                    id: 'appointments',
                    activerecord_class: Appointment::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ap_id', primary: true)
                        ->add_foreign('physician_id')
                        ->add_foreign('patient_id')
                        ->add_date('appointment_date'),
                    association_builder: fn(AssociationBuilder $a) => $a
                        ->belongs_to('physicians', local_key: 'physician_id')
                        ->belongs_to('patients', local_key: 'patient_id'),
                ),
                'patients' => $config->add_model(
                    id: 'patients',
                    activerecord_class: Patient::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('pa_id', primary: true)
                        ->add_varchar('name'),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many('appointments', foreign_key: 'patient_id')
                        ->has_many('physicians', foreign_key: 'patient_id', through: 'appointments'),
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
