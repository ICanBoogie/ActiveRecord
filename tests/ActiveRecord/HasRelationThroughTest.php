<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\HasManyRelation;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\ConfigBuilder\Fixture;
use Test\ICanBoogie\Acme\ConfigBuilder\Magazine;
use Test\ICanBoogie\Acme\ConfigBuilder\Publication;
use Test\ICanBoogie\Acme\ConfigBuilder\Reader;
use Test\ICanBoogie\Acme\ConfigBuilder\Subscription;
use Test\ICanBoogie\Acme\ConfigBuilder\Team;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

use function array_column;

/**
 * Tests the relations through a pivot, whatever their kind.
 */
#[Group("db")]
final class HasRelationThroughTest extends DbTestCase
{
    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    private function install(ConfigBuilder $builder): ModelRegistry
    {
        $config = $builder->build();
        $models = new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);

        new ModelInstaller($models)->install();

        StaticModelProvider::set(fn() => $models);

        return $models;
    }

    /**
     * The relations are only defined by the configs of the tests, the records don't declare their
     * getters, so they are called from the relations of the model.
     *
     * @param non-empty-string $as
     *
     * @return array<mixed>
     */
    private function related(ModelRegistry $models, ActiveRecord $record, string $as): array
    {
        $relation = $models->model_for_record($record::class)->relations->get($as);

        $this->assertInstanceOf(HasManyRelation::class, $relation);

        return $relation($record)->all;
    }

    public function test_related_record_has_the_columns_of_its_parent(): void
    {
        // The pivot references `Publication`, the parent of `Magazine`, which holds its title.
        $models = $this->install(
            Fixtures::with_main_connection(new ConfigBuilder())
                ->add_record(Reader::class, association_builder: fn(AssociationBuilder $association) => $association
                    ->has_many(Magazine::class, through: Subscription::class))
                ->add_record(Publication::class)
                ->add_record(Magazine::class)
                ->add_record(Subscription::class)
        );

        $reader = Reader::from([ 'name' => "Alice" ])->save();
        $magazine = Magazine::from([ 'title' => "Monthly", 'issue' => 42 ])->save();
        Subscription::from([ 'reader_id' => $reader->reader_id, 'publication_id' => $magazine->publication_id ])
            ->save();

        $actual = $this->related($models, $reader, 'magazines');

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(Magazine::class, $actual[0]);
        $this->assertSame("Monthly", $actual[0]->title);
        $this->assertSame(42, $actual[0]->issue);
    }

    public function test_pivot_referencing_the_owner_and_the_related_record_with_the_same_class(): void
    {
        $models = $this->install(
            Fixtures::with_main_connection(new ConfigBuilder())
                ->add_record(Team::class, association_builder: fn(AssociationBuilder $association) => $association
                    ->has_many(Team::class, foreign_key: 'home_team_id', through: Fixture::class, as: 'guests'))
                ->add_record(Fixture::class)
        );

        $lions = Team::from([ 'name' => "Lions" ])->save();
        $tigers = Team::from([ 'name' => "Tigers" ])->save();
        Team::from([ 'name' => "Bears" ])->save();
        Fixture::from([ 'home_team_id' => $lions->team_id, 'away_team_id' => $tigers->team_id ])->save();

        $lions_guests = array_column($this->related($models, $lions, 'guests'), 'name');
        $tigers_guests = array_column($this->related($models, $tigers, 'guests'), 'name');

        $this->assertSame([ "Tigers" ], $lions_guests);
        $this->assertSame([], $tigers_guests);
    }
}
