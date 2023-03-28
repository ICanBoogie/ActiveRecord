<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\SchemaColumn;
use ICanBoogie\ActiveRecord\SchemaIndex;
use LogicException;
use PHPUnit\Framework\TestCase;

final class SchemaBuilderTest extends TestCase
{
    public function test_build(): void
    {
        $actual = (new SchemaBuilder())
            ->add_boolean('is_active')
            ->add_integer('rating_count')
            ->add_decimal('rating_avg', null: true)
            ->add_character('country', size: 2, fixed: true)
            ->add_character('title')
            ->add_text('body')
            ->add_datetime('date', default: SchemaBuilder::CURRENT_TIMESTAMP)
            ->add_index('is_active')
            ->build();

        $expected = new Schema(
            columns: [
                'is_active' => SchemaColumn::boolean(),
                'rating_count' => SchemaColumn::int(),
                'rating_avg' => SchemaColumn::float(null: true),
                'country' => SchemaColumn::character(2, fixed: true),
                'title' => SchemaColumn::character(),
                'body' => SchemaColumn::text(),
                'date' => SchemaColumn::datetime(default: SchemaColumn::CURRENT_TIMESTAMP)
            ],
            indexes: [
                new SchemaIndex([ 'is_active' ])
            ]);

        $this->assertEquals($expected, $actual);
    }

    public function test_fail_on_index_with_undefined_column(): void
    {
        $builder = (new SchemaBuilder())->add_boolean('is_active');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Column used by index is not defined: madonna");
        $builder->add_index([ 'madonna' ]);
    }
}
