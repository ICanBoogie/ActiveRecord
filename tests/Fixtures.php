<?php

namespace Test\ICanBoogie;

use ICanBoogie\Acme\CommentModel;
use ICanBoogie\Acme\HasMany\Appointment;
use ICanBoogie\Acme\HasMany\Patient;
use ICanBoogie\Acme\HasMany\Physician;
use ICanBoogie\Acme\Node;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\SchemaColumn;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Driver;

use function array_fill_keys;
use function array_intersect_key;

final class Fixtures
{
    public static function connections_with_primary(): ConnectionCollection
    {
        return new ConnectionCollection([

            'primary' => 'sqlite::memory:'

        ]);
    }

    /**
     * @return array<string, array<Model::*, mixed>>
     */
    public static function model_definitions(array $some = null): array
    {
        $definitions = [

            // CMS stuff

            'nodes' => [
                Model::ACTIVERECORD_CLASS => Node::class,
                Model::SCHEMA => new Schema([
                    'nid' => SchemaColumn::serial(primary: true),
                    'title' => SchemaColumn::varchar(),
                ])
            ],

            'articles' => [
                Model::CLASSNAME => ArticleModel::class,
                Model::ACTIVERECORD_CLASS => Article::class,
                Model::HAS_MANY => 'comments',
                Model::SCHEMA => new Schema([
                    'body' => SchemaColumn::varchar(),
                    'date' => SchemaColumn::datetime(default: 'CURRENT_TIMESTAMP'),
                    'rating' => SchemaColumn::int(size: SchemaColumn::SIZE_TINY, null: true),
                ]),
                Model::EXTENDING => 'nodes',
            ],

            'comments' => [
                Model::CLASSNAME => CommentModel::class,
                Model::ACTIVERECORD_CLASS => Comment::class,
                Model::SCHEMA => new Schema([
                    'comment_id' => SchemaColumn::serial(primary: true),
                    'nid' => SchemaColumn::foreign(),
                    'body' => SchemaColumn::text(),
                ])
            ],

            // Car stuff

            'drivers' => [
                Model::ACTIVERECORD_CLASS => Driver::class,
                Model::SCHEMA => new Schema([
                    'driver_id' => SchemaColumn::serial(primary: true),
                    'name' => SchemaColumn::varchar(),
                ])
            ],

            'brands' => [
                Model::ACTIVERECORD_CLASS => Brand::class,
                Model::SCHEMA => new Schema([
                    'brand_id' => SchemaColumn::serial(primary: true),
                    'name' => SchemaColumn::varchar(),
                ])
            ],

            'cars' => [
                Model::ACTIVERECORD_CLASS => Car::class,
                Model::SCHEMA => new Schema([
                    'driver_id' => SchemaColumn::foreign(),
                    'brand_id' => SchemaColumn::foreign(),
                ])
            ],

            // Has many through

            'physicians' => [
                Model::ACTIVERECORD_CLASS => Physician::class,
                Model::SCHEMA => (new SchemaBuilder())
                    ->add_serial('ph_id', primary: true)
                    ->add_varchar('name')
                    ->build(),
                Model::HAS_MANY => [
                    [ 'appointments', [ 'local_key' => 'ph_id', 'foreign_key' => 'physician_id' ] ],
                    [ 'patients', [ 'foreign_key' => 'pa_id', 'through' => 'appointments' ] ],
                ]
            ],

            'appointments' => [
                Model::ACTIVERECORD_CLASS => Appointment::class,
                Model::SCHEMA => (new SchemaBuilder())
                    ->add_serial('ap_id', primary: true)
                    ->add_foreign('physician_id')
                    ->add_foreign('patient_id')
                    ->add_date('appointment_date')
                    ->build(),
                Model::BELONGS_TO => [
                    [ 'physicians', [ 'local_key' => 'physician_id', 'foreign_key' => 'ph_id' ] ],
                    [ 'patients', [ 'local_key' => 'patient_id', 'foreign_key' => 'pa_id' ] ],
                ]
            ],

            'patients' => [
                Model::ACTIVERECORD_CLASS => Patient::class,
                Model::SCHEMA => (new SchemaBuilder())
                    ->add_serial('pa_id', primary: true)
                    ->add_varchar('name')
                    ->build(),
                Model::HAS_MANY => [
                    [ 'appointments', [ 'local_key' => 'pa_id', 'foreign_key' => 'patient_id' ] ],
                    [ 'physicians', [ 'local_key' => 'pa_id', 'foreign_key' => 'patient_id', 'through' => 'appointments' ] ],
                ]
            ],

        ];

        if (!$some) {
            return $definitions;
        }

        return array_intersect_key(
            $definitions,
            array_fill_keys($some, 1),
        );
    }
}
