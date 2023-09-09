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
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\BrandModel;
use Test\ICanBoogie\Acme\CarModel;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\CommentModel;
use Test\ICanBoogie\Acme\CountModel;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\DriverModel;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\AppointmentModel;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\PatientModel;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\Acme\HasMany\PhysicianModel;
use Test\ICanBoogie\Acme\NodeModel;
use Test\ICanBoogie\Acme\Subscriber;
use Test\ICanBoogie\Acme\SubscriberModel;
use Test\ICanBoogie\Acme\UpdateModel;

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
                    model_class: NodeModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('nid', primary: true)
                        ->add_character('title'),
                ),
                'articles' => $config->add_model(
                    model_class: ArticleModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_character('body')
                        ->add_datetime('date', default: DateTime::CURRENT_TIMESTAMP)
                        ->add_integer('rating', size: Integer::SIZE_TINY, null: true),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many(Comment::class, foreign_key: 'nid')
                        ->has_many(Comment::class, foreign_key: 'nid', as: 'article_comments') // testing 'as'
                ),
                'comments' => $config->add_model(
                    model_class: CommentModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('comment_id', primary: true)
                        ->belongs_to('nid', Article::class)
                        ->add_text('body'),
                ),
                'counts' => $config->add_model(
                    model_class: CountModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('id', primary: true)
                        ->add_character('name')
                        ->add_datetime('date'),
                ),
                #
                # car&drivers
                #
                'drivers' => $config->add_model(
                    model_class: DriverModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('driver_id', primary: true)
                        ->add_character('name')
                ),
                'brands' => $config->add_model(
                    model_class: BrandModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('brand_id', primary: true)
                        ->add_character('name'),
                ),
                'cars' => $config->add_model(
                    model_class: CarModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('car_id', primary: true)
                        ->belongs_to('driver_id', Driver::class)
                        ->belongs_to('brand_id', Brand::class)
                        ->add_character('name'),
                ),
                #
                #
                #
                'subscribers' => $config->add_model(
                    model_class: SubscriberModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('subscriber_id', primary: true)
                        ->add_character('email'),
                ),
                'updates' => $config->add_model(
                    model_class: UpdateModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('updated_id', primary: true)
                        ->belongs_to('subscriber_id', Subscriber::class)
                        ->add_datetime('updated_at')
                        ->add_character('updated_hash', size: 40, fixed: true),
                ),
                #
                #
                #
                'physicians' => $config->add_model(
                    model_class: PhysicianModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ph_id', primary: true)
                        ->add_character('name'),
                    association_builder: fn(AssociationBuilder $association) => $association
                        ->has_many(Appointment::class, foreign_key: 'physician_id')
                        ->has_many(Patient::class, through: Appointment::class),
                ),
                'appointments' => $config->add_model(
                    model_class: AppointmentModel::class,
                    schema_builder: fn(SchemaBuilder $schema) => $schema
                        ->add_serial('ap_id', primary: true)
                        ->belongs_to('physician_id', Physician::class)
                        ->belongs_to('patient_id', Patient::class)
                        ->add_date('appointment_date'),
                ),
                'patients' => $config->add_model(
                    model_class: PatientModel::class,
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
