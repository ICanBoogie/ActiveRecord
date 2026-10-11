<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\AmbiguousRelation;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\RelationNotFound;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\Subscriber;
use Test\ICanBoogie\Acme\Update;
use Test\ICanBoogie\Fixtures;

final class RelationCollectionTest extends TestCase
{
    public function test_columns_to_from_relation_of_the_model(): void
    {
        $models = Fixtures::only_models('subscribers', 'updates');

        $this->assertSame(
            [ 'subscriber_id', 'subscriber_id' ],
            $models->model_for_record(Update::class)->relations
                ->columns_to($models->model_for_record(Subscriber::class))
        );
    }

    public function test_columns_to_from_relation_of_the_other_model(): void
    {
        $models = Fixtures::only_models('subscribers', 'updates');

        $this->assertSame(
            [ 'subscriber_id', 'subscriber_id' ],
            $models->model_for_record(Subscriber::class)->relations
                ->columns_to($models->model_for_record(Update::class))
        );
    }

    /**
     * A relation to a parent class links the models of its children, and a relation and its
     * inverse count as one.
     */
    public function test_columns_to_with_parent_class_and_inverse_relation(): void
    {
        $models = Fixtures::only_models('nodes', 'articles', 'comments');

        $this->assertSame(
            [ 'nid', 'nid' ],
            $models->model_for_record(Article::class)->relations
                ->columns_to($models->model_for_record(Comment::class))
        );
    }

    public function test_columns_to_with_different_column_names(): void
    {
        $models = $this->build_cars(
            brand: fn(SchemaBuilder $schema) => $schema->add_serial('id', primary: true),
        );

        $this->assertSame(
            [ 'brand_id', 'id' ],
            $models->model_for_record(Car::class)->relations
                ->columns_to($models->model_for_record(Brand::class))
        );
        $this->assertSame(
            [ 'id', 'brand_id' ],
            $models->model_for_record(Brand::class)->relations
                ->columns_to($models->model_for_record(Car::class))
        );
    }

    public function test_join_with_different_column_names_uses_on(): void
    {
        $models = $this->build_cars(
            brand: fn(SchemaBuilder $schema) => $schema->add_serial('id', primary: true),
        );
        $cars = $models->model_for_record(Car::class);
        $quote = $cars->connection->quote_identifier(...);

        $this->assertStringEndsWith(
            ' INNER JOIN ' . $quote('brands') . ' AS ' . $quote('brand')
            . ' ON ' . $quote('car') . '.' . $quote('brand_id') . ' = ' . $quote('brand') . '.' . $quote('id'),
            (string) $cars->query()->join(record: Brand::class)
        );
    }

    public function test_columns_to_throws_when_no_relation_links_the_models(): void
    {
        $models = Fixtures::only_models('subscribers', 'nodes');
        $subscribers = $models->model_for_record(Subscriber::class);
        $nodes = $models->model_for_record(Node::class);

        try {
            $subscribers->relations->columns_to($nodes);
            $this->fail("Expected " . RelationNotFound::class);
        } catch (RelationNotFound $e) {
            $this->assertSame($subscribers, $e->model);
            $this->assertSame($nodes, $e->other);
        }
    }

    public function test_columns_to_throws_when_more_than_one_relation_links_the_models(): void
    {
        $models = $this->build_cars(
            brand: fn(SchemaBuilder $schema) => $schema->add_serial('brand_id', primary: true),
            car: fn(SchemaBuilder $schema) => $schema
                ->add_serial('car_id', primary: true)
                ->belongs_to('brand_id', Brand::class)
                ->belongs_to('previous_brand_id', Brand::class, as: 'previous_brand'),
        );
        $cars = $models->model_for_record(Car::class);
        $brands = $models->model_for_record(Brand::class);

        try {
            $cars->relations->columns_to($brands);
            $this->fail("Expected " . AmbiguousRelation::class);
        } catch (AmbiguousRelation $e) {
            $this->assertSame($cars, $e->model);
            $this->assertSame($brands, $e->other);
            $this->assertCount(2, $e->relations);
        }
    }

    /**
     * @param callable(SchemaBuilder): SchemaBuilder $brand
     * @param (callable(SchemaBuilder): SchemaBuilder)|null $car
     */
    private function build_cars(callable $brand, ?callable $car = null): ModelRegistry
    {
        $config = Fixtures::with_main_connection(new ConfigBuilder())
            ->add_record(record_class: Driver::class, schema_builder: fn(SchemaBuilder $schema) => $schema
                ->add_serial('driver_id', primary: true))
            ->add_record(record_class: Brand::class, schema_builder: $brand(...))
            ->add_record(record_class: Car::class, schema_builder: $car ?? fn(SchemaBuilder $schema) => $schema
                ->add_serial('car_id', primary: true)
                ->belongs_to('brand_id', Brand::class))
            ->build();

        return new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);
    }
}
