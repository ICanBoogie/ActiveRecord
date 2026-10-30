<?php

namespace Test\ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\BelongsToRelation;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\RecordNotFound;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\DanceSession;
use Test\ICanBoogie\Acme\Equipment;
use Test\ICanBoogie\Acme\Person;
use Test\ICanBoogie\Acme\PersonEquipment;
use Test\ICanBoogie\Acme\Skill;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group("db")]
final class PreloadBelongsToTest extends DbTestCase
{
    /**
     * @var Model<Car>
     */
    private Model $cars;
    private BelongsToRelation $brand;
    private Brand $smart;
    private Brand $fiat;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('drivers', 'brands', 'cars');

        new ModelInstaller($models)->install();
        StaticModelProvider::set(fn() => $models);

        $this->cars = $models->model_for_record(Car::class);
        $relation = $this->cars->relations->get('brand');
        assert($relation instanceof BelongsToRelation);
        $this->brand = $relation;

        $this->smart = Brand::from([ 'name' => "Smart" ])->save();
        $this->fiat = Brand::from([ 'name' => "Fiat" ])->save();
    }

    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_is_preloadable(): void
    {
        $this->assertTrue($this->brand->is_preloadable());
    }

    public function test_preload_runs_one_query_and_the_getter_none(): void
    {
        $cars = $this->create_cars($this->smart, $this->fiat, $this->smart);

        $preload = $this->count_queries(fn() => $this->brand->preload($cars));
        $names = [];
        $read = $this->count_queries(function () use ($cars, &$names) {
            foreach ($cars as $car) {
                $names[] = $car->brand->name;
            }
        });

        $this->assertSame(1, $preload);
        $this->assertSame(0, $read);
        $this->assertSame([ "Smart", "Fiat", "Smart" ], $names);
    }

    public function test_owners_with_the_same_key_share_the_record(): void
    {
        [ $a, $b ] = $cars = $this->create_cars($this->smart, $this->smart);

        $this->brand->preload($cars);

        $this->assertSame($a->brand, $b->brand);
    }

    public function test_preload_skips_loaded_owners(): void
    {
        $cars = $this->create_cars($this->smart, $this->fiat);
        $this->brand->preload($cars);

        $actual = $this->count_queries(fn() => $this->brand->preload($cars));

        $this->assertSame(0, $actual);
    }

    public function test_preload_only_queries_the_owners_that_are_not_loaded(): void
    {
        [ $a, $b ] = $cars = $this->create_cars($this->smart, $this->fiat);
        $this->brand->preload([ $a ]);

        $actual = $this->count_queries(fn() => $this->brand->preload($cars));
        $read = $this->count_queries(fn() => $b->brand);

        $this->assertSame(1, $actual);
        $this->assertSame(0, $read);
    }

    public function test_preload_without_records_runs_no_query(): void
    {
        $actual = $this->count_queries(fn() => $this->brand->preload([]));

        $this->assertSame(0, $actual);
    }

    public function test_changing_the_key_makes_the_loaded_record_stale(): void
    {
        [ $car ] = $cars = $this->create_cars($this->smart);
        $this->brand->preload($cars);

        $car->brand_id = $this->fiat->brand_id;
        $actual = $car->brand;

        $this->assertSame($this->fiat->brand_id, $actual->brand_id);
    }

    public function test_a_clone_is_not_loaded(): void
    {
        [ $car ] = $cars = $this->create_cars($this->smart);
        $this->brand->preload($cars);
        $clone = clone $car;

        $actual = $this->count_queries(fn() => $clone->brand);

        $this->assertSame(1, $actual);
    }

    public function test_dangling_key_is_not_loaded_and_the_getter_throws_as_before(): void
    {
        [ $dangling, $valid ] = $cars = $this->create_cars(null, $this->smart);

        $this->brand->preload($cars);

        $this->assertSame("Smart", $valid->brand->name);
        $this->expectException(RecordNotFound::class);
        $dangling->brand; // @phpstan-ignore expr.resultUnused
    }

    public function test_null_keys_are_skipped(): void
    {
        $models = $this->build_people_models();
        $people = $models->model_for_record(Person::class);
        $hire_skill = $people->relations->get('hire_skill');
        $alone = Person::from([ 'name' => "Alone", 'hire_skill_id' => null ])->save();

        $preload = $this->count_queries(fn() => $hire_skill->preload([ $alone ]), $people);
        $read = $this->count_queries(fn() => $alone->hire_skill, $people);

        $this->assertSame(0, $preload);
        $this->assertSame(0, $read);
    }

    /**
     * @return Car[] A car per brand, a `null` brand being a key that doesn't match any row.
     */
    private function create_cars(?Brand ...$brands): array
    {
        $cars = [];

        foreach ($brands as $i => $brand) {
            $cars[] = Car::from([
                'name' => "Car $i",
                'driver_id' => 1,
                'brand_id' => $brand->brand_id ?? 9999,
            ])->save();
        }

        return $cars;
    }

    /**
     * @param Closure(): mixed $closure
     * @param Model<*>|null $model
     */
    private function count_queries(Closure $closure, ?Model $model = null): int
    {
        $telemetry = ($model ?? $this->cars)->connection->telemetry;
        $before = $telemetry->execute_count;

        $closure();

        return $telemetry->execute_count - $before;
    }

    private function build_people_models(): ModelRegistry
    {
        $builder = Fixtures::with_main_connection(new ConfigBuilder());

        foreach ([ Skill::class, DanceSession::class, Equipment::class, Person::class, PersonEquipment::class ] as $c) {
            $builder->add_record($c);
        }

        $config = $builder->build();
        $models = new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);

        new ModelInstaller($models)->install();
        StaticModelProvider::set(fn() => $models);

        return $models;
    }
}
