<?php

namespace Test\ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\RelationNotDefined;
use ICanBoogie\ActiveRecord\RelationNotPreloadable;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use LogicException;
use PDO;
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
final class QueryIncludesTest extends DbTestCase
{
    /**
     * @var Model<Car>
     */
    private Model $cars;
    private Brand $smart;
    private Brand $fiat;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('drivers', 'brands', 'cars');

        new ModelInstaller($models)->install();
        StaticModelProvider::set(fn() => $models);

        $this->cars = $models->model_for_record(Car::class);
        $this->smart = Brand::from([ 'name' => "Smart" ])->save();
        $this->fiat = Brand::from([ 'name' => "Fiat" ])->save();

        foreach ([ $this->smart, $this->fiat, $this->smart ] as $i => $brand) {
            Car::from([ 'name' => "Car $i", 'driver_id' => 1, 'brand_id' => $brand->brand_id ])->save();
        }
    }

    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_includes_returns_the_query(): void
    {
        $query = $this->cars->query();

        $actual = $query->includes('brand');

        $this->assertSame($query, $actual);
    }

    public function test_all_preloads_the_relations(): void
    {
        $names = [];

        $total = $this->count_queries(function () use (&$names) {
            $names = $this->read_brand_names($this->cars->query()->includes('brand')->all());
        });

        $this->assertSame(2, $total);
        $this->assertSame([ "Smart", "Fiat", "Smart" ], $names);
    }

    public function test_the_all_property_preloads_the_relations(): void
    {
        $total = $this->count_queries(function () {
            $this->read_brand_names($this->cars->query()->includes('brand')->all);
        });

        $this->assertSame(2, $total);
    }

    public function test_without_includes_the_relation_queries_per_record(): void
    {
        $total = $this->count_queries(function () {
            $this->read_brand_names($this->cars->query()->all);
        });

        $this->assertSame(1 + 3, $total);
    }

    public function test_the_iterator_preloads_each_batch(): void
    {
        $total = $this->count_queries(function () {
            $this->read_brand_names($this->cars->query()->includes('brand')->batch_size(2));
        });

        // 2 batches of cars (2 + 1), a preload for each.
        $this->assertSame(4, $total);
    }

    public function test_includes_is_applied_to_a_filtered_query(): void
    {
        $total = $this->count_queries(function () {
            $cars = $this->cars->query()->includes('brand')->where([ 'car_id' => [ 1, 2 ] ])->all();

            $this->read_brand_names($cars);
        });

        $this->assertSame(2, $total);
    }

    public function test_includes_is_kept_by_clone(): void
    {
        $query = $this->cars->query()->includes('brand');

        $total = $this->count_queries(function () use ($query) {
            $this->read_brand_names((clone $query)->all());
        });

        $this->assertSame(2, $total);
    }

    public function test_includes_twice_with_the_same_name_preloads_once(): void
    {
        $total = $this->count_queries(function () {
            $this->read_brand_names($this->cars->query()->includes('brand')->includes('brand')->all());
        });

        $this->assertSame(2, $total);
    }

    public function test_one_ignores_includes(): void
    {
        $car = null;

        $total = $this->count_queries(function () use (&$car) {
            $car = $this->cars->query()->includes('brand')->one();
        });

        $this->assertSame(1, $total);
        $this->assertInstanceOf(Car::class, $car);
    }

    public function test_aggregates_ignore_includes(): void
    {
        $actual = $this->cars->query()->includes('brand')->count;

        $this->assertSame(3, $actual);
    }

    public function test_an_unknown_relation_is_refused_when_includes_is_called(): void
    {
        $this->expectException(RelationNotDefined::class);

        $this->cars->query()->includes('nope');
    }

    public function test_a_relation_that_cannot_be_preloaded_is_refused_when_includes_is_called(): void
    {
        $builder = Fixtures::with_main_connection(new ConfigBuilder());

        foreach ([ Skill::class, DanceSession::class, Equipment::class, Person::class, PersonEquipment::class ] as $c) {
            $builder->add_record($c);
        }

        $config = $builder->build();
        $models = new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);
        $people = $models->model_for_record(Person::class);

        $this->expectException(RelationNotPreloadable::class);

        $people->query()->includes('equipment');
    }

    public function test_a_select_with_includes_is_refused(): void
    {
        $query = $this->cars->query()->includes('brand')->select('car_id');

        $this->expectException(LogicException::class);

        $query->all();
    }

    public function test_a_mode_other_than_class_with_includes_is_refused(): void
    {
        $query = $this->cars->query()->includes('brand');

        $this->expectException(LogicException::class);

        $query->all(PDO::FETCH_COLUMN);
    }

    public function test_a_class_mode_with_flags_is_accepted(): void
    {
        $actual = $this->cars->query()->includes('brand')->all(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Car::class);

        $this->assertCount(3, $actual);
    }

    /**
     * @param iterable<mixed> $cars
     *
     * @return string[]
     */
    private function read_brand_names(iterable $cars): array
    {
        $names = [];

        foreach ($cars as $car) {
            assert($car instanceof Car);

            $names[] = $car->brand->name;
        }

        return $names;
    }

    /**
     * @param Closure(): mixed $closure
     */
    private function count_queries(Closure $closure): int
    {
        $telemetry = $this->cars->connection->telemetry;
        $before = $telemetry->execute_count;

        $closure();

        return $telemetry->execute_count - $before;
    }
}
