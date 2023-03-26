<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\SchemaIndex;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Fixtures;
use Test\ICanBoogie\SetStateHelper;

final class ConfigBuilderTest extends TestCase
{
    public function test_extends(): void
    {
        $config = (new ConfigBuilder())
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: 'sqlite::memory:',
            )
            ->add_model(
                id: 'nodes',
                activerecord_class: Node::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('nid', primary: true)
                    ->add_varchar('title'),
            )
            ->add_model(
                id: 'articles',
                activerecord_class: Article::class,
                model_class: ArticleModel::class,
                extends: 'nodes',
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('body')
                    ->add_datetime('date'),
            )
            ->build();

        $schema = $config->models['articles']->schema;

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertEquals('nid', $schema->primary);
        $this->assertFalse($schema['nid']->auto_increment);
    }

    public function test_from_attributes(): void
    {
        $config = (new ConfigBuilder())
            ->from_attributes()
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: 'sqlite::memory:',
            )
            ->add_model(
                id: 'nodes',
                activerecord_class: Node::class,
            )
            ->add_model(
                id: 'articles',
                activerecord_class: Article::class,
                model_class: ArticleModel::class,
                extends: 'nodes',
            )
            ->build();

        $schema = $config->models['articles']->schema;

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertEquals('nid', $schema->primary);
        $this->assertFalse($schema['nid']->auto_increment);
        $this->assertEquals([
            new SchemaIndex([ 'rating' ], name: 'idx_rating')
        ], $schema->indexes);
    }

    public function test_from_attributes_with_association(): void
    {
        $config = (new ConfigBuilder())
            ->from_attributes()
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: 'sqlite::memory:',
            )
            ->add_model(
                id: 'physicians',
                activerecord_class: Physician::class,
            )
            ->add_model(
                id: 'patients',
                activerecord_class: Patient::class,
            )
            ->add_model(
                id: 'appointments',
                activerecord_class: Appointment::class,
            )
            ->build();

        $ap_def = $config->models['appointments'];

        $this->assertNotNull($ap_def->association);
        $this->assertEquals([
            new Config\BelongsToAssociation('physicians', 'physician_id', 'ph_id', 'physician'),
            new Config\BelongsToAssociation('patients', 'patient_id', 'pa_id', 'patient'),
        ], $ap_def->association->belongs_to);
    }

    public function test_export(): void
    {
        $builder = new ConfigBuilder();

        Fixtures::with_main_connection($builder);
        Fixtures::with_models($builder, [ 'physicians', 'appointments', 'patients' ]);

        $config = $builder->build();
        $actual = SetStateHelper::export_import($config);

        $this->assertEquals($config, $actual);
    }
}
