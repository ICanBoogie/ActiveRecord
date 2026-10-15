<?php

namespace Test\ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\Config\InvalidConfig;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\Schema\ForeignKey;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\InvalidSchema;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\ConfigBuilder\Award;
use Test\ICanBoogie\Acme\ConfigBuilder\Bookmark;
use Test\ICanBoogie\Acme\ConfigBuilder\Catalog;
use Test\ICanBoogie\Acme\ConfigBuilder\CatalogEdition;
use Test\ICanBoogie\Acme\ConfigBuilder\Fixture;
use Test\ICanBoogie\Acme\ConfigBuilder\Ledger;
use Test\ICanBoogie\Acme\ConfigBuilder\LedgerEntry;
use Test\ICanBoogie\Acme\ConfigBuilder\Letter;
use Test\ICanBoogie\Acme\ConfigBuilder\Magazine;
use Test\ICanBoogie\Acme\ConfigBuilder\Publication;
use Test\ICanBoogie\Acme\ConfigBuilder\Reader;
use Test\ICanBoogie\Acme\ConfigBuilder\Receipt;
use Test\ICanBoogie\Acme\ConfigBuilder\Seat;
use Test\ICanBoogie\Acme\ConfigBuilder\Subscription;
use Test\ICanBoogie\Acme\ConfigBuilder\Tag;
use Test\ICanBoogie\Acme\ConfigBuilder\Tagging;
use Test\ICanBoogie\Acme\ConfigBuilder\Team;
use Test\ICanBoogie\Acme\ConfigBuilder\Ticket;
use Test\ICanBoogie\Acme\ConfigBuilder\Venue;
use Test\ICanBoogie\Acme\ConfigBuilder\VipSeat;
use Test\ICanBoogie\Acme\Discovery\Author as DiscoveredAuthor;
use Test\ICanBoogie\Acme\Discovery\Book as DiscoveredBook;
use Test\ICanBoogie\Acme\Discovery\Reviewer as DiscoveredReviewer;
use Test\ICanBoogie\SetStateHelper;
use Throwable;

/**
 * Tests how {@see ConfigBuilder} resolves the relationships between records.
 *
 * The records are defined in `tests/Acme/ConfigBuilder`.
 */
final class ConfigBuilderTest extends TestCase
{
    public function test_fragment_filename(): void
    {
        $this->assertSame('activerecord', ConfigBuilder::get_fragment_filename());
    }

    private static function builder(): ConfigBuilder
    {
        return new ConfigBuilder()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_connection('other', 'sqlite::memory:');
    }

    // Extending a record

    public function test_extended_record_shares_the_primary_key_of_its_parent(): void
    {
        $config = self::builder()
            ->add_record(Publication::class)
            ->add_record(Magazine::class)
            ->build();

        $schema = $config->models[Magazine::class]->table->schema;
        $publication_id = $schema->columns['publication_id'];

        $this->assertSame('publication_id', $schema->primary);
        $this->assertInstanceOf(Integer::class, $publication_id);
        $this->assertFalse($publication_id->serial, "the parent generates the identifier");
        $this->assertTrue($publication_id->unsigned, "same signedness as the parent's serial");
        $this->assertSame(Integer::SIZE_BIG, $publication_id->size, "same size as the parent's serial");
        $this->assertArrayHasKey('issue', $schema->columns);
    }

    public function test_extended_record_aligns_on_a_signed_primary_key(): void
    {
        $config = self::builder()
            ->add_record(Catalog::class)
            ->add_record(CatalogEdition::class)
            ->build();

        $catalog_id = $config->models[CatalogEdition::class]->table->schema->columns['catalog_id'];

        $this->assertInstanceOf(Integer::class, $catalog_id);
        $this->assertFalse($catalog_id->unsigned);
        $this->assertSame(Integer::SIZE_BIG, $catalog_id->size);
    }

    public function test_extended_record_requires_the_definition_of_its_parent(): void
    {
        $this->expectException(InvalidConfig::class);
        $this->expectExceptionMessage("`" . Magazine::class . "` extends `" . Publication::class . "`, which is not defined");

        self::builder()
            ->add_record(Magazine::class)
            ->build();
    }

    public function test_extended_record_requires_a_single_column_primary_key(): void
    {
        $this->expectException(InvalidConfig::class);
        $this->expectExceptionMessage("`" . VipSeat::class . "` cannot extend `" . Seat::class . "`");

        self::builder()
            ->add_record(Seat::class)
            ->add_record(VipSeat::class)
            ->build();
    }

    // BelongsTo

    public function test_belongs_to_associations(): void
    {
        $config = self::builder()
            ->add_record(Reader::class)
            ->add_record(Publication::class)
            ->add_record(Subscription::class)
            ->build();

        $association = $config->models[Subscription::class]->association;

        $this->assertNotNull($association);
        $this->assertEquals([
            new Config\BelongsToAssociation(Reader::class, 'reader_id', 'reader_id', 'reader'),
            new Config\BelongsToAssociation(Publication::class, 'publication_id', 'publication_id', 'publication'),
        ], $association->belongs_to);
    }

    public function test_belongs_to_requires_a_single_column_primary_key(): void
    {
        try {
            self::builder()
                ->add_record(Seat::class)
                ->add_record(Ticket::class)
                ->build();

            $this->fail("Expected InvalidConfig");
        } catch (InvalidConfig $e) {
            $this->assertStringStartsWith("Unable to apply", $e->getMessage());
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertStringContainsString("not a single column", $e->getPrevious()->getMessage());
        }
    }

    // schema_builder:

    public function test_schema_builder_defines_the_schema_of_a_record_without_attributes(): void
    {
        $config = self::builder()
            ->add_record(
                record_class: Ledger::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    // a signed integer on purpose, to check the alignment of the foreign key
                    ->add_integer('ledger_id', size: Integer::SIZE_BIG, primary: true),
            )
            ->add_record(
                record_class: Receipt::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('receipt_id', primary: true)
                    ->belongs_to('ledger_id', Ledger::class, on_delete: OnDelete::Cascade),
            )
            ->build();

        $schema = $config->models[Receipt::class]->table->schema;
        $ledger_id = $schema->columns['ledger_id'];

        $this->assertSame('receipt_id', $schema->primary);
        $this->assertEquals(
            [ new ForeignKey('ledger_id', 'ledgers', 'ledger_id', OnDelete::Cascade) ],
            $schema->foreign_keys
        );
        $this->assertInstanceOf(Integer::class, $ledger_id);
        $this->assertFalse($ledger_id->unsigned, "aligned on ledgers.ledger_id");
        $this->assertSame(Integer::SIZE_BIG, $ledger_id->size);
    }

    public function test_extended_record_defined_with_schema_builder_aligns_on_its_parent(): void
    {
        $config = self::builder()
            ->add_record(
                record_class: Ledger::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_integer('ledger_id', size: Integer::SIZE_BIG, primary: true),
            )
            ->add_record(
                record_class: LedgerEntry::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('memo'),
            )
            ->build();

        $ledger_id = $config->models[LedgerEntry::class]->table->schema->columns['ledger_id'];

        $this->assertInstanceOf(Integer::class, $ledger_id);
        $this->assertFalse($ledger_id->unsigned);
        $this->assertSame(Integer::SIZE_BIG, $ledger_id->size);
    }

    public function test_schema_builder_completes_the_columns_of_attributes(): void
    {
        $config = self::builder()
            ->add_record(
                record_class: Team::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_integer('founded_in', null: true),
            )
            ->build();

        $schema = $config->models[Team::class]->table->schema;

        $this->assertSame('team_id', $schema->primary);
        $this->assertSame([ 'team_id', 'name', 'founded_in' ], array_keys($schema->columns));
    }

    public function test_schema_builder_cannot_redefine_the_columns_of_attributes(): void
    {
        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessage("Column `name` is already defined (record `" . Team::class . "`)");

        self::builder()
            ->add_record(
                record_class: Team::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('name'),
            );
    }

    public function test_record_without_attributes_requires_a_schema_builder(): void
    {
        $this->expectException(InvalidConfig::class);
        $this->expectExceptionMessage("The schema of `" . Ledger::class . "` is empty");

        self::builder()
            ->add_record(Ledger::class);
    }

    // Foreign keys

    public function test_foreign_keys_are_opt_in(): void
    {
        $config = self::builder()
            ->add_record(Reader::class)
            ->add_record(Publication::class)
            ->add_record(Subscription::class)
            ->build();

        $this->assertEquals(
            [ new ForeignKey('reader_id', 'readers', 'reader_id', OnDelete::Cascade) ],
            $config->models[Subscription::class]->table->schema->foreign_keys,
            "only the BelongsTo with an on_delete creates a foreign key"
        );
        $this->assertSame([], $config->models[Reader::class]->table->schema->foreign_keys);
    }

    public function test_foreign_key_aligns_on_the_referenced_primary_key(): void
    {
        $config = self::builder()
            ->add_record(Reader::class)
            ->add_record(Publication::class)
            ->add_record(Subscription::class)
            ->build();

        $reader_id = $config->models[Subscription::class]->table->schema->columns['reader_id'];

        $this->assertInstanceOf(Integer::class, $reader_id);
        $this->assertTrue($reader_id->unsigned, "aligned on readers.reader_id");
        $this->assertSame(Integer::SIZE_REGULAR, $reader_id->size);
    }

    public function test_foreign_key_to_a_child_table(): void
    {
        $config = self::builder()
            ->add_record(Publication::class)
            ->add_record(Magazine::class)
            ->add_record(Letter::class)
            ->build();

        $schema = $config->models[Letter::class]->table->schema;
        $magazine_id = $schema->columns['magazine_id'];

        $this->assertEquals(
            [ new ForeignKey('magazine_id', 'magazines', 'publication_id', OnDelete::Cascade) ],
            $schema->foreign_keys
        );
        $this->assertInstanceOf(Integer::class, $magazine_id);
        $this->assertTrue($magazine_id->unsigned, "aligned on magazines.publication_id, itself aligned on publications");
        $this->assertSame(Integer::SIZE_BIG, $magazine_id->size);
    }

    /**
     * @param Closure(ConfigBuilder): ConfigBuilder $configure
     */
    #[DataProvider('provide_invalid_foreign_key')]
    public function test_invalid_foreign_key(Closure $configure, string $expected): void
    {
        try {
            $configure(self::builder())->build();

            $this->fail("Expected InvalidConfig");
        } catch (InvalidConfig $e) {
            $this->assertStringStartsWith("Unable to create foreign key", $e->getMessage());
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertStringContainsString($expected, $e->getPrevious()->getMessage());
        }
    }

    /**
     * @return iterable<string, array{ Closure(ConfigBuilder): ConfigBuilder, string }>
     */
    public static function provide_invalid_foreign_key(): iterable
    {
        yield "SetNull on a column that isn't nullable" => [
            fn(ConfigBuilder $builder) => $builder
                ->add_record(Team::class)
                ->add_record(Bookmark::class),
            "must be nullable",
        ];

        yield "Referenced table on another connection" => [
            fn(ConfigBuilder $builder) => $builder
                ->add_record(Reader::class, connection: 'other')
                ->add_record(Publication::class)
                ->add_record(Subscription::class),
            "another connection",
        ];

        yield "Primary key that isn't an integer" => [
            fn(ConfigBuilder $builder) => $builder
                ->add_record(Tag::class)
                ->add_record(Tagging::class),
            "not an integer",
        ];
    }

    // HasMany

    public function test_has_many_associations_from_attributes(): void
    {
        $config = self::builder()
            ->add_record(Reader::class)
            ->add_record(Publication::class)
            ->add_record(Subscription::class)
            ->build();

        $association = $config->models[Reader::class]->association;

        $this->assertNotNull($association);
        $this->assertEquals([
            new Config\HasManyAssociation(Subscription::class, 'reader_id', 'subscriptions', null),
            new Config\HasManyAssociation(Publication::class, 'publication_id', 'publications', Subscription::class),
        ], $association->has_many);
    }

    public function test_has_many_infers_the_foreign_key_from_the_belongs_to_of_the_ancestor(): void
    {
        $config = self::builder()
            ->add_record(Reader::class)
            ->add_record(Publication::class)
            ->add_record(
                record_class: Magazine::class,
                association_builder: fn(AssociationBuilder $association) => $association
                    ->has_many(Subscription::class),
            )
            ->add_record(Subscription::class)
            ->build();

        $association = $config->models[Magazine::class]->association;

        $this->assertNotNull($association);
        $this->assertEquals([
            new Config\HasManyAssociation(Subscription::class, 'publication_id', 'subscriptions', null),
        ], $association->has_many);
    }

    public function test_has_many_prefers_the_belongs_to_of_the_record_over_the_ancestor(): void
    {
        $config = self::builder()
            ->add_record(Publication::class)
            ->add_record(
                record_class: Magazine::class,
                association_builder: fn(AssociationBuilder $association) => $association
                    ->has_many(Award::class),
            )
            ->add_record(Award::class)
            ->build();

        $association = $config->models[Magazine::class]->association;

        $this->assertNotNull($association);
        $this->assertSame('magazine_id', $association->has_many[0]->foreign_key);
    }

    public function test_has_many_foreign_key_resolves_the_ambiguity(): void
    {
        $config = self::builder()
            ->add_record(
                record_class: Team::class,
                association_builder: fn(AssociationBuilder $association) => $association
                    ->has_many(Fixture::class, foreign_key: 'home_team_id', as: 'home_fixtures')
                    ->has_many(Fixture::class, foreign_key: 'away_team_id', as: 'away_fixtures'),
            )
            ->add_record(Fixture::class)
            ->build();

        $association = $config->models[Team::class]->association;

        $this->assertNotNull($association);
        $this->assertEquals([
            new Config\HasManyAssociation(Fixture::class, 'home_team_id', 'home_fixtures', null),
            new Config\HasManyAssociation(Fixture::class, 'away_team_id', 'away_fixtures', null),
        ], $association->has_many);
    }

    public function test_has_many_requires_the_definition_of_the_associate(): void
    {
        try {
            self::builder()
                ->add_record(Team::class, association_builder: fn(AssociationBuilder $association) => $association
                    ->has_many(Fixture::class))
                ->build();

            $this->fail("Expected InvalidConfig");
        } catch (InvalidConfig $e) {
            $this->assertStringStartsWith("Unable to apply", $e->getMessage());
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertStringContainsString(
                "The associated record `" . Fixture::class . "` is not defined",
                $e->getPrevious()->getMessage()
            );
        }
    }

    public function test_has_many_requires_the_definition_of_the_through_record(): void
    {
        try {
            self::builder()
                ->add_record(Team::class, association_builder: fn(AssociationBuilder $association) => $association
                    ->has_many(Fixture::class, through: Venue::class))
                ->add_record(Fixture::class)
                ->build();

            $this->fail("Expected InvalidConfig");
        } catch (InvalidConfig $e) {
            $this->assertStringStartsWith("Unable to apply", $e->getMessage());
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertStringContainsString(
                "The `through` record `" . Venue::class . "` is not defined",
                $e->getPrevious()->getMessage()
            );
        }
    }

    /**
     * @param Closure(AssociationBuilder): AssociationBuilder $association_builder
     */
    #[DataProvider('provide_invalid_has_many')]
    public function test_invalid_has_many(Closure $association_builder, string $expected): void
    {
        $builder = self::builder()
            ->add_record(Team::class, association_builder: $association_builder)
            ->add_record(Fixture::class)
            ->add_record(Venue::class);

        try {
            $builder->build();

            $this->fail("Expected InvalidConfig");
        } catch (InvalidConfig $e) {
            $this->assertStringStartsWith("Unable to apply", $e->getMessage());
            $this->assertInstanceOf(Throwable::class, $e->getPrevious());
            $this->assertStringContainsString($expected, $e->getPrevious()->getMessage());
        }
    }

    /**
     * @return iterable<string, array{ Closure(AssociationBuilder): AssociationBuilder, string }>
     */
    public static function provide_invalid_has_many(): iterable
    {
        yield "No BelongsTo column" => [
            fn(AssociationBuilder $association) => $association
                ->has_many(Venue::class),
            "has no BelongsTo column referencing",
        ];

        yield "Several BelongsTo columns" => [
            fn(AssociationBuilder $association) => $association
                ->has_many(Fixture::class),
            "several BelongsTo columns referencing",
        ];

        yield "Foreign key that isn't a column" => [
            fn(AssociationBuilder $association) => $association
                ->has_many(Fixture::class, foreign_key: 'pilot_id'),
            "has no column `pilot_id`",
        ];
    }

    // Export

    public function test_export(): void
    {
        $config = self::builder()
            ->add_record(Reader::class)
            ->add_record(Publication::class)
            ->add_record(Magazine::class)
            ->add_record(Subscription::class)
            ->add_record(Letter::class)
            ->build();

        $this->assertEquals($config, SetStateHelper::export_import($config));
    }

    // Discovery

    public function test_discovery_is_disabled_by_default(): void
    {
        $config = self::builder()->build();

        $this->assertSame([], array_keys($config->models));
    }

    /**
     * The records are collected from `tests/Acme/Discovery`.
     */
    public function test_discovery_adds_tagged_records(): void
    {
        $config = self::builder()->enable_discovery()->build();
        $classes = array_keys($config->models);
        sort($classes);

        $this->assertSame([ DiscoveredAuthor::class, DiscoveredBook::class, DiscoveredReviewer::class ], $classes);
        $this->assertSame(
            DiscoveredAuthor::class,
            $config->models[DiscoveredBook::class]->association?->belongs_to[0]->associate
        );
    }

    public function test_discovery_uses_the_record_attribute(): void
    {
        $definition = self::builder()->enable_discovery()->build()->models[DiscoveredReviewer::class];

        $this->assertSame('critics', $definition->table->name);
        $this->assertSame('critic', $definition->table->alias);
        $this->assertSame('other', $definition->connection);
    }

    public function test_discovery_keeps_explicit_records(): void
    {
        $config = self::builder()
            ->add_record(DiscoveredAuthor::class, table_name: 'writers')
            ->enable_discovery()
            ->build();

        $this->assertSame('writers', $config->models[DiscoveredAuthor::class]->table->name);
        $this->assertArrayHasKey(DiscoveredBook::class, $config->models);
    }

    public function test_discovery_can_be_disabled(): void
    {
        $config = self::builder()->enable_discovery()->disable_discovery()->build();

        $this->assertSame([], array_keys($config->models));
    }
}
