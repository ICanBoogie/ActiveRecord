<?php

namespace ICanBoogie\ActiveRecord;

use LogicException;
use PHPUnit\Framework\TestCase;
use Test\ICanBoogie\Acme\Brand;
use Test\ICanBoogie\Acme\Car;
use Test\ICanBoogie\Acme\Driver;
use Test\ICanBoogie\Fixtures;

use function is_int;

final class ModelBelongsToTest extends TestCase
{
    public function test_belongs_to(): void
    {
        [ , $models ] = Fixtures::only_models([ 'drivers', 'brands', 'cars' ]);

        $models->install();

        $drivers = $models['drivers'];
        $brands = $models['brands'];
        $cars = $models['cars'];

        $cars->belongs_to($drivers);
        $cars->belongs_to($brands);

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
