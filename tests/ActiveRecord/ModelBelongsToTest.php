<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\Config;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\DanceSession;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\Equipment;
use Test\ICanBoogie\Acme\Person;
use Test\ICanBoogie\Acme\PersonEquipment;
use Test\ICanBoogie\Acme\Skill;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

use function is_int;

#[Group("db")]
final class ModelBelongsToTest extends DbTestCase
{
    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_belongs_to_runtime(): void
    {
        $models = Fixtures::only_models('drivers', 'brands', 'cars');

        new ModelInstaller($models)->install();
        StaticModelProvider::set(fn() => $models);

        $car = new Car();
        $car->name = '4two';

        try {
            /** @phpstan-ignore-next-line */
            $car->driver;
            /** @phpstan-ignore-next-line */
        } catch (LogicException $e) {
            $this->assertStringStartsWith("Unable to establish relation", $e->getMessage());
        }

        try {
            /** @phpstan-ignore-next-line */
            $car->brand;
            /** @phpstan-ignore-next-line */
        } catch (LogicException $e) {
            $this->assertStringStartsWith("Unable to establish relation", $e->getMessage());
        }

        # driver

        $driver = new Driver();
        $driver->name = 'Madonna';
        $driver->save();
        $driver_id = $driver->driver_id;

        # brand

        $brand = new Brand();
        $brand->name = 'Smart';
        $brand->save();
        $brand_id = $brand->brand_id;

        $car->driver_id = $driver_id;
        $car->brand_id = $brand_id;
        $car->save();

        $this->assertInstanceOf(Driver::class, $car->driver);
        $this->assertInstanceOf(Brand::class, $car->brand);

        $car->driver = $driver;
        $this->assertEquals($driver->driver_id, $car->driver_id);
    }

    #[Test]
    public function getter_is_created_from_the_column_name_without_id_suffix(): void
    {
        $config = new ConfigBuilder()
            ->use_attributes()
            ->add_connection(Config::DEFAULT_CONNECTION_ID, 'sqlite::memory:')
            ->add_record(record_class: Skill::class)
            ->add_record(record_class: DanceSession::class)
            ->add_record(record_class: Equipment::class)
            ->add_record(record_class: Person::class)
            ->add_record(record_class: PersonEquipment::class)
            ->build();

        $connections = new ConnectionRegistry($config->connections);
        $models = new ModelRegistry($connections, $config->models);

        $people = $models->model_for_record(Person::class);

        $this->assertTrue($people->relations->has('dance_session'));
        $this->assertTrue($people->relations->has('hire_skill'));
        $this->assertTrue($people->relations->has('summon_skill'));
        $this->assertTrue($people->relations->has('teach_skill'));
    }
}
