<?php

namespace Test\ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\Config\InvalidConfig;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\Schema\ForeignKey;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\ForeignKey\Author;
use Test\ICanBoogie\Acme\ForeignKey\Book;
use Test\ICanBoogie\Acme\ForeignKey\Review;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Fixtures;
use Test\ICanBoogie\SetStateHelper;
use Throwable;

final class ConfigBuilderTest extends TestCase
{
    public function test_extends(): void
    {
        $config = new ConfigBuilder()
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

        $nid = $schema->columns['nid'];
        $this->assertInstanceOf(Integer::class, $nid);
        $this->assertTrue($nid->unsigned, "same signedness as the parent's serial");
    }

    public function test_extends_signed_primary_key(): void
    {
        $config = new ConfigBuilder()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_record(
                record_class: Node::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    // create a signed integer on purpose, to check alignment
                    ->add_integer('nid', size: Integer::SIZE_BIG, primary: true),
            )
            ->add_record(
                record_class: Article::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('body'),
            )
            ->build();

        $nid = $config->models[Article::class]->table->schema->columns['nid'];

        $this->assertInstanceOf(Integer::class, $nid);
        $this->assertFalse($nid->unsigned);
        $this->assertSame(Integer::SIZE_BIG, $nid->size);
    }

    public function test_foreign_key_to_a_child_table(): void
    {
        $config = new ConfigBuilder()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_record(
                record_class: Node::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('nid', primary: true),
            )
            ->add_record(
                record_class: Article::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_text('body'),
            )
            ->add_record(
                record_class: Comment::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('comment_id', primary: true)
                    ->belongs_to('nid', Article::class, on_delete: OnDelete::Cascade),
            )
            ->build();

        $nid = $config->models[Comment::class]->table->schema->columns['nid'];

        $this->assertInstanceOf(Integer::class, $nid);
        $this->assertTrue($nid->unsigned, "aligned on articles.nid, which is aligned on nodes.nid");
    }

    public function test_from_attributes(): void
    {
        $config = new ConfigBuilder()
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
        $config = new ConfigBuilder()
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

    public function test_foreign_keys_from_attributes(): void
    {
        $config = new ConfigBuilder()
            ->use_attributes()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_record(Author::class)
            ->add_record(Book::class)
            ->add_record(Review::class)
            ->add_record(Node::class)
            ->add_record(Article::class)
            ->add_record(Comment::class)
            ->build();

        $book = $config->models[Book::class]->table->schema;

        $this->assertEquals(
            [ new ForeignKey('author_id', 'authors', 'id', OnDelete::Cascade) ],
            $book->foreign_keys
        );

        $author_id = $book->columns['author_id'];
        $this->assertInstanceOf(Integer::class, $author_id);
        $this->assertTrue($author_id->unsigned, "aligned on the serial primary key");

        $this->assertEquals(
            [ new ForeignKey('book_id', 'books', 'id', OnDelete::SetNull) ],
            $config->models[Review::class]->table->schema->foreign_keys
        );

        $this->assertSame([], $config->models[Author::class]->table->schema->foreign_keys);
        $this->assertSame(
            [],
            $config->models[Comment::class]->table->schema->foreign_keys,
            "a BelongsTo without on_delete doesn't create a foreign key"
        );
    }

    public function test_foreign_key_aligns_on_the_size_of_the_primary_key(): void
    {
        $config = new ConfigBuilder()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_record(
                record_class: Brand::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('id', size: Integer::SIZE_BIG, primary: true),
            )
            ->add_record(
                record_class: Driver::class,
                schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('id', primary: true)
                    ->belongs_to('brand_id', Brand::class, on_delete: OnDelete::Cascade),
            )
            ->build();

        $brand_id = $config->models[Driver::class]->table->schema->columns['brand_id'];

        $this->assertInstanceOf(Integer::class, $brand_id);
        $this->assertSame(Integer::SIZE_BIG, $brand_id->size);
    }

    public function test_foreign_key_export(): void
    {
        $config = new ConfigBuilder()
            ->use_attributes()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_record(Author::class)
            ->add_record(Book::class)
            ->build();

        $this->assertEquals($config, SetStateHelper::export_import($config));
    }

    /**
     * @param Closure(ConfigBuilder): ConfigBuilder $configure
     */
    #[DataProvider('provide_invalid_foreign_key')]
    public function test_invalid_foreign_key(Closure $configure, string $expected): void
    {
        $builder = new ConfigBuilder()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_connection('other', 'sqlite::memory:');

        try {
            $configure($builder)->build();
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
        $driver = fn(OnDelete $on_delete, bool $null = false) => fn(SchemaBuilder $schema) => $schema
            ->add_serial('id', primary: true)
            ->belongs_to('brand_id', Brand::class, null: $null, on_delete: $on_delete);

        yield "SetNull on a column that isn't nullable" => [
            fn(ConfigBuilder $builder) => $builder
                ->add_record(Brand::class, schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('id', primary: true))
                ->add_record(Driver::class, schema_builder: $driver(OnDelete::SetNull)),
            "must be nullable",
        ];

        yield "Referenced table on another connection" => [
            fn(ConfigBuilder $builder) => $builder
                ->add_record(Brand::class, schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_serial('id', primary: true), connection: 'other')
                ->add_record(Driver::class, schema_builder: $driver(OnDelete::Cascade)),
            "another connection",
        ];

        yield "Primary key that isn't an integer" => [
            fn(ConfigBuilder $builder) => $builder
                ->add_record(Brand::class, schema_builder: fn(SchemaBuilder $schema) => $schema
                    ->add_character('code', primary: true))
                ->add_record(Driver::class, schema_builder: $driver(OnDelete::Cascade)),
            "not an integer",
        ];
    }
}
