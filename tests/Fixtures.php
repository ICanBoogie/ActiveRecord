<?php

namespace Test\ICanBoogie;

use ICanBoogie\Acme\CommentModel;
use ICanBoogie\Acme\Count;
use ICanBoogie\Acme\HasMany\Appointment;
use ICanBoogie\Acme\HasMany\Patient;
use ICanBoogie\Acme\HasMany\Physician;
use ICanBoogie\Acme\Node;
use ICanBoogie\Acme\Subscriber;
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
use Test\ICanBoogie\Acme\Driver;

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
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('nid', primary: true)
                        ->add_varchar('title'),
                    activerecord_class: Node::class,
                ),
                'articles' => $config->add_model(
                    id: 'articles',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_varchar('body')
                        ->add_datetime('date', default: $schema::CURRENT_TIMESTAMP)
                        ->add_integer('rating', size: $schema::SIZE_TINY, null: true),
                    activerecord_class: Article::class,
                    extends: 'nodes',
                    model_class: ArticleModel::class,
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many('comments', foreign_key: 'nid')
                ),
                'comments' => $config->add_model(
                    id: 'comments',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('comment_id', primary: true)
                        ->add_foreign('nid')
                        ->add_text('body'),
                    activerecord_class: Comment::class,
                    model_class: CommentModel::class,
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->belongs_to('articles', local_key: 'nid')
                ),
                'counts' => $config->add_model(
                    id: 'counts',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('id', primary: true)
                        ->add_varchar('name')
                        ->add_datetime('date'),
                    activerecord_class: Count::class,
                ),
                #
                # car&drivers
                #
                'drivers' => $config->add_model(
                    'drivers',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('driver_id', primary: true)
                        ->add_varchar('name'),
                    activerecord_class: Driver::class
                ),
                'brands' => $config->add_model(
                    id: 'brands',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('brand_id', primary: true)
                        ->add_varchar('name'),
                    activerecord_class: Brand::class,
                ),
                'cars' => $config->add_model(
                    id: 'cars',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('car_id', primary: true)
                        ->add_foreign('driver_id')
                        ->add_foreign('brand_id')
                        ->add_varchar('name'),
                    activerecord_class: Car::class,
                ),
                #
                #
                #
                'subscribers' => $config->add_model(
                    id: 'subscribers',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('subscriber_id', primary: true)
                        ->add_varchar('email'),
                    activerecord_class: Subscriber::class,
                ),
                'updates' => $config->add_model(
                    id: 'updates',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('updated_id', primary: true)
                        ->add_foreign('subscriber_id')
                        ->add_datetime('updated_at')
                        ->add_char('updated_hash', size: 40),
                    activerecord_class: Subscriber::class,
                ),
                #
                #
                #
                'physicians' => $config->add_model(
                    id: 'physicians',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ph_id', primary: true)
                        ->add_varchar('name'),
                    activerecord_class: Physician::class,
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many('appointments', foreign_key: 'physician_id')
                        ->has_many('patients', through: 'appointments'),
                ),
                'appointments' => $config->add_model(
                    id: 'appointments',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ap_id', primary: true)
                        ->add_foreign('physician_id')
                        ->add_foreign('patient_id')
                        ->add_date('appointment_date'),
                    activerecord_class: Appointment::class,
                    association_builder: fn(AssociationBuilder $a) => $a
                        ->belongs_to('physicians', local_key: 'physician_id')
                        ->belongs_to('patients', local_key: 'patient_id'),
                ),
                'patients' => $config->add_model(
                    id: 'patients',
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('pa_id', primary: true)
                        ->add_varchar('name'),
                    activerecord_class: Patient::class,
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
