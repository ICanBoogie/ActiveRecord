<?php

namespace Test\ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\HasManyRelation;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\RelationNotPreloadable;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\HasMany\Appointment;
use Test\ICanBoogie\Acme\HasMany\Patient;
use Test\ICanBoogie\Acme\HasMany\Physician;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group("db")]
final class PreloadHasManyTest extends DbTestCase
{
    /**
     * @var Model<Physician>
     */
    private Model $physicians;
    private HasManyRelation $appointments;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('physicians', 'appointments', 'patients');

        new ModelInstaller($models)->install();
        StaticModelProvider::set(fn() => $models);

        $this->physicians = $models->model_for_record(Physician::class);
        $relation = $this->physicians->relations->get('appointments');
        assert($relation instanceof HasManyRelation);
        $this->appointments = $relation;
    }

    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_is_preloadable_without_through(): void
    {
        $this->assertTrue($this->appointments->is_preloadable());
    }

    public function test_is_not_preloadable_through_a_pivot(): void
    {
        $patients = $this->physicians->relations->get('patients');

        $this->assertFalse($patients->is_preloadable());

        $this->expectException(RelationNotPreloadable::class);

        $patients->preload([]);
    }

    public function test_preload_runs_one_query_and_the_loaded_getter_none(): void
    {
        [ $alice, $bob, $carol ] = $physicians = $this->create_physicians();

        $preload = $this->count_queries(fn() => $this->appointments->preload($physicians));
        $read = $this->count_queries(function () use ($alice, $bob, $carol, &$counts) {
            $counts = [
                count($alice->appointments_loaded),
                count($bob->appointments_loaded),
                count($carol->appointments_loaded),
            ];
        });

        $this->assertSame(1, $preload);
        $this->assertSame(0, $read);
        $this->assertSame([ 2, 1, 0 ], $counts);
    }

    public function test_only_the_related_records_of_the_owner_are_attached(): void
    {
        [ $alice, $bob ] = $physicians = $this->create_physicians();

        $this->appointments->preload($physicians);

        $actual = [ $alice->appointments_loaded, $bob->appointments_loaded ];

        foreach ($actual[0] as $appointment) {
            $this->assertSame($alice->ph_id, $appointment->physician_id);
        }

        foreach ($actual[1] as $appointment) {
            $this->assertSame($bob->ph_id, $appointment->physician_id);
        }
    }

    public function test_the_getter_still_returns_a_query_when_loaded(): void
    {
        [ $alice ] = $physicians = $this->create_physicians();
        $this->appointments->preload($physicians);

        $actual = $alice->appointments;

        $this->assertInstanceOf(Query::class, $actual);
        $this->assertCount(2, $actual->all);
    }

    public function test_the_loaded_getter_fetches_when_not_preloaded(): void
    {
        [ $alice ] = $this->create_physicians();
        $actual = [];

        $queries = $this->count_queries(function () use ($alice, &$actual) {
            $actual = $alice->appointments_loaded;
        });

        $this->assertSame(1, $queries);
        $this->assertCount(2, $actual);
    }

    public function test_the_loaded_getter_fetches_for_a_relation_through_a_pivot(): void
    {
        [ $alice ] = $this->create_physicians();

        $actual = $alice->patients_loaded;

        $this->assertCount(2, $actual);
    }

    public function test_includes_preloads_the_relation_with_one_query(): void
    {
        $this->create_physicians();

        $queries = $this->count_queries(function () use (&$counts) {
            $counts = [];

            foreach ($this->physicians->query()->includes('appointments')->all as $physician) {
                assert($physician instanceof Physician);

                $counts[] = count($physician->appointments_loaded);
            }
        });

        $this->assertSame(2, $queries);
        $this->assertSame([ 2, 1, 0 ], $counts);
    }

    public function test_preload_skips_loaded_owners(): void
    {
        $physicians = $this->create_physicians();
        $this->appointments->preload($physicians);

        $actual = $this->count_queries(fn() => $this->appointments->preload($physicians));

        $this->assertSame(0, $actual);
    }

    public function test_preload_without_records_runs_no_query(): void
    {
        $actual = $this->count_queries(fn() => $this->appointments->preload([]));

        $this->assertSame(0, $actual);
    }

    public function test_assigning_another_key_invalidates_the_loaded_records(): void
    {
        [ $alice, $bob ] = $physicians = $this->create_physicians();
        $this->appointments->preload($physicians);
        $alice->ph_id = $bob->ph_id;
        $actual = [];

        $queries = $this->count_queries(function () use ($alice, &$actual) {
            $actual = $alice->appointments_loaded;
        });

        $this->assertSame(1, $queries);
        $this->assertCount(1, $actual);
    }

    public function test_a_clone_is_not_loaded(): void
    {
        [ $alice ] = $physicians = $this->create_physicians();
        $this->appointments->preload($physicians);
        $clone = clone $alice;

        $actual = $this->count_queries(fn() => $clone->appointments_loaded);

        $this->assertSame(1, $actual);
    }

    /**
     * @return Physician[] Alice has two appointments, Bob has one, Carol has none.
     */
    private function create_physicians(): array
    {
        $alice = Physician::from([ 'name' => "Alice" ])->save();
        $bob = Physician::from([ 'name' => "Bob" ])->save();
        $carol = Physician::from([ 'name' => "Carol" ])->save();
        $patient_1 = Patient::from([ 'name' => "Patient 1" ])->save();
        $patient_2 = Patient::from([ 'name' => "Patient 2" ])->save();

        foreach ([ [ $alice, $patient_1 ], [ $alice, $patient_2 ], [ $bob, $patient_1 ] ] as [ $physician, $patient ]) {
            Appointment::from([
                'physician_id' => $physician->ph_id,
                'patient_id' => $patient->pa_id,
                'appointment_date' => '2026-10-31 10:00:00',
            ])->save();
        }

        return [ $alice, $bob, $carol ];
    }

    /**
     * @param Closure(): mixed $closure
     */
    private function count_queries(Closure $closure): int
    {
        $telemetry = $this->physicians->connection->telemetry;
        $before = $telemetry->execute_count;

        $closure();

        return $telemetry->execute_count - $before;
    }
}
