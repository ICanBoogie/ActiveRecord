<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use LogicException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;

final class SchemaBuilderTest extends TestCase
{
    public function test_build(): void
    {
        $actual = (new SchemaBuilder())
            ->add_serial('nid', primary: true)
            ->add_boolean('is_active')
            ->add_integer('rating_count')
            ->add_decimal('rating_avg', 5, null: true)
            ->add_character('country', size: 2, fixed: true)
            ->add_character('title')
            ->add_text('body')
            ->add_datetime('date', default: DateTime::CURRENT_TIMESTAMP)
            ->add_index('is_active')
            ->build();

        $expected = new Schema(
            columns: [
                'nid' => new Schema\Serial(),
                'is_active' => new Schema\Boolean(),
                'rating_count' => new Schema\Integer(),
                'rating_avg' => new Schema\Decimal(5, null: true),
                'country' => new Schema\Character(2, fixed: true),
                'title' => new Schema\Character(),
                'body' => new Schema\Text(),
                'date' => new DateTime(default: DateTime::CURRENT_TIMESTAMP)
            ],
            primary: 'nid',
            indexes: [
                new Schema\Index('is_active')
            ],
        );

        $this->assertEquals($expected, $actual);
    }

    public function test_use_record(): void
    {
        $expected = (new SchemaBuilder())
            ->add_text('body')
            ->add_date('date')
            ->add_integer('rating', null: true)
            ->add_index('rating', name: 'idx_rating')
            ->build();

        $actual = (new SchemaBuilder())
            ->use_record(Article::class)
            ->build();

        $this->assertEquals($expected, $actual);
    }

    public function test_build_fails_when_index_uses_undefined_column(): void
    {
        $builder = (new SchemaBuilder())
            ->add_boolean('is_active')
            ->add_index('madonna');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Column used by index is not defined: madonna");
        $builder->build();
    }
}
