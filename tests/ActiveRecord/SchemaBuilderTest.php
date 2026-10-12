<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\InvalidSchema;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\ICanBoogie\Acme\SchemaBuilder\Entry;
use Test\ICanBoogie\Acme\SchemaBuilder\EntryRevision;
use Test\ICanBoogie\Acme\SchemaBuilder\NoAttributes;
use Test\ICanBoogie\Acme\SchemaBuilder\PrimaryWithoutColumn;
use Test\ICanBoogie\Acme\SchemaBuilder\Writer;

final class SchemaBuilderTest extends TestCase
{
    public function test_build(): void
    {
        $actual = new SchemaBuilder()
            ->add_serial('nid', primary: true)
            ->add_boolean('is_active')
            ->add_boolean('is_featured', default: false)
            ->add_integer('rating_count')
            ->add_decimal('rating_avg', 5, null: true)
            ->add_character('country', size: 2, fixed: true)
            ->add_character('title')
            ->add_binary('binary1', size: 31, null: true, unique: true)
            ->add_binary('binary2', size: 32, fixed: true)
            ->add_blob('blob1', size: Schema\Blob::SIZE_LONG, null: true, unique: true)
            ->add_blob('blob2')
            ->add_text('body')
            ->add_datetime('date', default: DateTime::CURRENT_TIMESTAMP)
            ->add_time('opens_at')
            ->add_time('closes_at', null: true, default: Schema\Time::CURRENT_TIME)
            ->add_index('is_active')
            ->build();

        $expected = new Schema(
            columns: [
                'nid' => new Schema\Serial(),
                'is_active' => new Schema\Boolean(),
                'is_featured' => new Schema\Boolean(default: false),
                'rating_count' => new Schema\Integer(),
                'rating_avg' => new Schema\Decimal(5, null: true),
                'country' => new Schema\Character(2, fixed: true),
                'title' => new Schema\Character(),
                'binary1' => new Schema\Binary(size: 31, null: true, unique: true),
                'binary2' => new Schema\Binary(size: 32, fixed: true),
                'blob1' => new Schema\Blob(size: Schema\Blob::SIZE_LONG, null: true, unique: true),
                'blob2' => new Schema\Blob(),
                'body' => new Schema\Text(),
                'date' => new DateTime(default: DateTime::CURRENT_TIMESTAMP),
                'opens_at' => new Schema\Time(),
                'closes_at' => new Schema\Time(null: true, default: Schema\Time::CURRENT_TIME),
            ],
            primary: 'nid',
            indexes: [
                new Schema\Index('is_active')
            ],
        );

        $this->assertEquals($expected, $actual);
    }

    public function test_record_class(): void
    {
        $this->assertNull(new SchemaBuilder()->record_class);
        $this->assertSame(Entry::class, new SchemaBuilder(Entry::class)->record_class);
    }

    public function test_record_class_must_extend_activerecord(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains(stdClass::class);

        // @phpstan-ignore-next-line // on purpose
        new SchemaBuilder(stdClass::class);
    }

    public function test_use_record(): void
    {
        $expected = new SchemaBuilder()
            ->add_serial('entry_id', primary: true)
            ->belongs_to('writer_id', Writer::class, on_delete: OnDelete::Cascade)
            ->add_character('title')
            ->add_text('body', null: true)
            ->add_index('title', name: 'idx_title')
            ->build();

        $actual = new SchemaBuilder(Entry::class)
            ->build();

        $this->assertEquals($expected, $actual);
    }

    public function test_use_record_ignores_parent_columns(): void
    {
        $expected = new SchemaBuilder()
            ->add_integer('revision', null: true)
            ->build();

        $actual = new SchemaBuilder(EntryRevision::class)
            ->build();

        $this->assertEquals($expected, $actual);
    }

    public function test_is_empty(): void
    {
        $this->assertTrue(new SchemaBuilder()->is_empty());
        $this->assertTrue(new SchemaBuilder(NoAttributes::class)->is_empty());
        $this->assertFalse(new SchemaBuilder(Entry::class)->is_empty());
    }

    public function test_add_column_fails_when_defined_by_record(): void
    {
        $builder = new SchemaBuilder(Entry::class);

        try {
            $builder->add_text('body');
        } catch (InvalidSchema $e) {
            $this->assertSame(Entry::class, $e->record_class);
            $this->assertSame(
                "Column `body` is already defined (record `" . Entry::class . "`)",
                $e->getMessage()
            );

            return;
        }

        $this->fail("Expected " . InvalidSchema::class);
    }

    public function test_add_column_fails_when_already_defined(): void
    {
        $builder = new SchemaBuilder()->add_boolean('is_active');

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains("Column `is_active` is already defined");

        $builder->add_integer('is_active');
    }

    public function test_add_column_fails_with_empty_name(): void
    {
        $builder = new SchemaBuilder();

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains("A column name must not be empty");

        // @phpstan-ignore-next-line
        $builder->add_boolean('');
    }

    public function test_add_column_fails_when_primary_key_defined_by_record(): void
    {
        $builder = new SchemaBuilder(Entry::class);

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains("Column `entry_id` is already defined");

        $builder->add_serial('entry_id', primary: true);
    }

    public function test_add_index_fails_when_already_defined(): void
    {
        $builder = new SchemaBuilder()
            ->add_boolean('is_active')
            ->add_index('is_active');

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains("An index with the columns `is_active` already exists");

        $builder->add_index('is_active', unique: true);
    }

    public function test_add_index_fails_when_defined_by_record(): void
    {
        $builder = new SchemaBuilder(Entry::class);

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains("An index with the columns `title` already exists");

        $builder->add_index('title');
    }

    public function test_add_index_with_different_column_order_is_distinct(): void
    {
        $schema = new SchemaBuilder()
            ->add_integer('a')
            ->add_integer('b')
            ->add_index([ 'a', 'b' ])
            ->add_index([ 'b', 'a' ])
            ->build();

        $this->assertCount(2, $schema->indexes);
    }

    public function test_build_fails_when_empty(): void
    {
        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains("The schema has no columns");

        new SchemaBuilder()->build();
    }

    public function test_build_fails_when_index_uses_undefined_column(): void
    {
        $builder = new SchemaBuilder()
            ->add_boolean('is_active')
            ->add_index('madonna');

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains("The column `madonna`, used by an index, is not defined");
        $builder->build();
    }

    public function test_build_fails_when_primary_key_is_not_a_column(): void
    {
        $builder = new SchemaBuilder(PrimaryWithoutColumn::class)
            ->add_boolean('is_active');

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains(
            "The column `id`, used as primary key, is not defined (record `" . PrimaryWithoutColumn::class . "`)"
        );
        $builder->build();
    }

    public function test_build_decorates_errors_with_record_class(): void
    {
        $builder = new SchemaBuilder(Entry::class)
            ->add_index('madonna');

        $this->expectException(InvalidSchema::class);
        $this->expectExceptionMessageIsOrContains(
            "The column `madonna`, used by an index, is not defined (record `" . Entry::class . "`)"
        );
        $builder->build();
    }
}
