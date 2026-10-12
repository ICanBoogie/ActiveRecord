<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\AmbiguousRelation;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\RelationNotFound;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Article;
use Test\ICanBoogie\Acme\Comment;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\Acme\RelationCollection\Maker;
use Test\ICanBoogie\Acme\RelationCollection\UsedVehicle;
use Test\ICanBoogie\Acme\RelationCollection\Vehicle;
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
        $models = $this->build_models(Maker::class, Vehicle::class);

        $this->assertSame(
            [ 'maker_id', 'id' ],
            $models->model_for_record(Vehicle::class)->relations
                ->columns_to($models->model_for_record(Maker::class))
        );
        $this->assertSame(
            [ 'id', 'maker_id' ],
            $models->model_for_record(Maker::class)->relations
                ->columns_to($models->model_for_record(Vehicle::class))
        );
    }

    public function test_join_with_different_column_names_uses_on(): void
    {
        $models = $this->build_models(Maker::class, Vehicle::class);
        $vehicles = $models->model_for_record(Vehicle::class);
        $quote = $vehicles->connection->quote_identifier(...);

        $this->assertStringEndsWith(
            ' INNER JOIN ' . $quote('makers') . ' AS ' . $quote('maker')
            . ' ON ' . $quote('vehicle') . '.' . $quote('maker_id') . ' = ' . $quote('maker') . '.' . $quote('id'),
            (string) $vehicles->query()->join(record: Maker::class)
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
        $models = $this->build_models(Maker::class, UsedVehicle::class);
        $vehicles = $models->model_for_record(UsedVehicle::class);
        $makers = $models->model_for_record(Maker::class);

        try {
            $vehicles->relations->columns_to($makers);
            $this->fail("Expected " . AmbiguousRelation::class);
        } catch (AmbiguousRelation $e) {
            $this->assertSame($vehicles, $e->model);
            $this->assertSame($makers, $e->other);
            $this->assertCount(2, $e->relations);
        }
    }

    /**
     * @param class-string<ActiveRecord> ...$record_classes
     */
    private function build_models(string ...$record_classes): ModelRegistry
    {
        $builder = Fixtures::with_main_connection(new ConfigBuilder());

        foreach ($record_classes as $record_class) {
            $builder->add_record($record_class);
        }

        $config = $builder->build();

        return new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);
    }
}
