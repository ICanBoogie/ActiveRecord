<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\ArticleModel;
use Test\ICanBoogie\Acme\HasMany\AppointmentModel;
use Test\ICanBoogie\Acme\HasMany\PatientModel;
use Test\ICanBoogie\Acme\HasMany\PhysicianModel;
use Test\ICanBoogie\Acme\NodeModel;
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
                model_class: NodeModel::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('nid', primary: true)
                    ->add_character('title'),
            )
            ->add_model(
                model_class: ArticleModel::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('body')
                    ->add_datetime('date'),
            )
            ->build();

        $schema = $config->models[ArticleModel::class]->table->schema;

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertEquals('nid', $schema->primary);
        $this->assertFalse($schema->columns['nid']->serial);
    }

    public function test_from_attributes(): void
    {
        $config = (new ConfigBuilder())
            ->use_attributes()
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: 'sqlite::memory:',
            )
            ->add_model(NodeModel::class)
            ->add_model(ArticleModel::class)
            ->build();

        $schema = $config->models[ArticleModel::class]->table->schema;

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertEquals('nid', $schema->primary);
        $this->assertFalse($schema->columns['nid']->serial);
        $this->assertEquals([
            new Schema\Index('rating', name: 'idx_rating')
        ], $schema->indexes);
    }

    public function test_from_attributes_with_association(): void
    {
        $config = (new ConfigBuilder())
            ->use_attributes()
            ->add_connection(
                id: Config::DEFAULT_CONNECTION_ID,
                dsn: 'sqlite::memory:',
            )
            ->add_model(PhysicianModel::class)
            ->add_model(PatientModel::class)
            ->add_model(AppointmentModel::class)
            ->build();

        $ph_def = $config->models[PhysicianModel::class];
        $ap_def = $config->models[AppointmentModel::class];

        $this->assertNotNull($ap_def->association);
        $this->assertEquals([
            new Config\BelongsToAssociation(PhysicianModel::class, 'physician_id', 'ph_id', 'physician'),
            new Config\BelongsToAssociation(PatientModel::class, 'patient_id', 'pa_id', 'patient'),
        ], $ap_def->association->belongs_to);
        $this->assertEquals([
            new Config\HasManyAssociation(AppointmentModel::class, 'ph_id', 'physician_id', 'appointments', null),
            new Config\HasManyAssociation(PatientModel::class, 'ph_id', 'pa_id', 'patients', AppointmentModel::class),
        ], $ph_def->association->has_many);
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
