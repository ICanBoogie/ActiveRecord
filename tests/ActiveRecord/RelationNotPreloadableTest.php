<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\Exception;
use ICanBoogie\ActiveRecord\HasManyRelation;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\RelationNotPreloadable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\DanceSession;
use Test\ICanBoogie\Acme\Equipment;
use Test\ICanBoogie\Acme\Person;
use Test\ICanBoogie\Acme\PersonEquipment;
use Test\ICanBoogie\Acme\Skill;
use Test\ICanBoogie\Fixtures;

final class RelationNotPreloadableTest extends TestCase
{
    private ModelRegistry $models;

    protected function setUp(): void
    {
        $builder = Fixtures::with_main_connection(new ConfigBuilder());

        foreach ([ Skill::class, DanceSession::class, Equipment::class, Person::class, PersonEquipment::class ] as $c) {
            $builder->add_record($c);
        }

        $config = $builder->build();
        $this->models = new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);
    }

    public function test_exception(): void
    {
        $relation = $this->models->model_for_record(Person::class)->relations->get('equipment');

        $exception = new RelationNotPreloadable($relation);

        $this->assertInstanceOf(LogicException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame($relation, $exception->relation);
        $this->assertSame(
            "The relation `equipment` of " . Person::class . " cannot be preloaded.",
            $exception->getMessage()
        );
    }

    public function test_has_many_is_not_preloadable(): void
    {
        $relation = $this->models->model_for_record(Skill::class)->relations->get('hires');

        $this->assertInstanceOf(HasManyRelation::class, $relation);
        $this->assertFalse($relation->is_preloadable());
    }

    public function test_has_many_through_is_not_preloadable(): void
    {
        $relation = $this->models->model_for_record(Person::class)->relations->get('equipment');

        $this->assertFalse($relation->is_preloadable());
    }

    public function test_preload_throws_when_the_relation_is_not_preloadable(): void
    {
        $relation = $this->models->model_for_record(Skill::class)->relations->get('hires');

        try {
            $relation->preload([]);
            $this->fail("Expected " . RelationNotPreloadable::class);
        } catch (RelationNotPreloadable $e) {
            $this->assertSame($relation, $e->relation);
        }
    }
}
