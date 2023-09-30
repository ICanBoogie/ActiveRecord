<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Comment;
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
            ->add_record(
                record_class: Node::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('nid', primary: true)
                    ->add_character('title'),
            )
            ->add_record(
                record_class: Article::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('body')
                    ->add_datetime('date'),
            )
            ->build();

        $schema = $config->models[Article::class]->table->schema;

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
            ->add_record(Node::class)
            ->add_record(Article::class)
            ->add_record(Comment::class)
            ->build();

        $schema = $config->models[Article::class]->table->schema;

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
            ->add_record(Physician::class)
            ->add_record(Patient::class)
            ->add_record(Appointment::class)
            ->build();

        $ph_def = $config->models[Physician::class];
        $ap_def = $config->models[Appointment::class];

        $this->assertNotNull($ap_def->association);
        $this->assertEquals([
            new Config\BelongsToAssociation(Physician::class, 'physician_id', 'ph_id', 'physician'),
            new Config\BelongsToAssociation(Patient::class, 'patient_id', 'pa_id', 'patient'),
        ], $ap_def->association->belongs_to);
        $this->assertEquals([
            new Config\HasManyAssociation(Appointment::class, 'physician_id', 'appointments', null),
            new Config\HasManyAssociation(Patient::class, 'pa_id', 'patients', Appointment::class),
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
