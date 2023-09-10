<?php

namespace Test\ICanBoogie\ActiveRecord;

use LogicException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\BrandModel;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\CarModel;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Acme\DriverModel;
use Test\ICanBoogie\Fixtures;

use function is_int;

final class ModelBelongsToTest extends TestCase
{
    public function test_belongs_to(): void
    {
        $models = Fixtures::only_models('drivers', 'brands', 'cars');

        $models->install();

        $drivers = $models->model_for_record(Driver::class);
        $brands = $models->model_for_record(Brand::class);
        $cars = $models->model_for_record(Car::class);

        /* @var $car Car */
        $car = $cars->new([ 'name' => '4two' ]);
        $this->assertInstanceOf(Car::class, $car);

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

        $driver = $drivers->new([ 'name' => 'Madonna' ]);
        $this->assertInstanceOf(Driver::class, $driver);
        $driver_id = $driver->save();

        # brand

        $brand = $brands->new([ 'name' => 'Smart' ]);
        $this->assertInstanceOf(Brand::class, $brand);
        $brand_id = $brand->save();

        assert(is_int($driver_id));
        assert(is_int($brand_id));

        $car->driver_id = $driver_id;
        $car->brand_id = $brand_id;
        $car->save();

        $this->assertInstanceOf(Driver::class, $car->driver);
        $this->assertInstanceOf(Brand::class, $car->brand);

        $car->driver = $driver;
        $this->assertEquals($driver->driver_id, $car->driver_id);
    }
}
