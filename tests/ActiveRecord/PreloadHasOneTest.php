<?php

namespace Test\ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord\HasOneRelation;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\RelationNotPreloadable;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\HasOne\Badge;
use Test\ICanBoogie\Acme\HasOne\Desk;
use Test\ICanBoogie\Acme\HasOne\DeskAssignment;
use Test\ICanBoogie\Acme\HasOne\Employee;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group("db")]
final class PreloadHasOneTest extends DbTestCase
{
    /**
     * @var Model<Employee>
     */
    private Model $employees;
    private HasOneRelation $badge;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('employees', 'badges', 'desks', 'desk_assignments');

        new ModelInstaller($models)->install();
        StaticModelProvider::set(fn() => $models);

        $this->employees = $models->model_for_record(Employee::class);
        $relation = $this->employees->relations->get('badge');
        assert($relation instanceof HasOneRelation);
        $this->badge = $relation;
    }

    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_is_preloadable_without_through(): void
    {
        $this->assertTrue($this->badge->is_preloadable());
    }

    public function test_is_not_preloadable_through_a_pivot(): void
    {
        $desk = $this->employees->relations->get('desk');

        $this->assertFalse($desk->is_preloadable());

        $this->expectException(RelationNotPreloadable::class);

        $desk->preload([]);
    }

    public function test_preload_runs_one_query_and_the_getter_none(): void
    {
        [ $alice, $bob, $carol ] = $employees = $this->create_employees();

        $preload = $this->count_queries(fn() => $this->badge->preload($employees));
        $read = $this->count_queries(function () use ($alice, $bob, $carol, &$numbers) {
            $numbers = [ $alice->badge?->number, $bob->badge?->number, $carol->badge?->number ];
        });

        $this->assertSame(1, $preload);
        $this->assertSame(0, $read);
        $this->assertSame([ "B-001", "B-002", null ], $numbers);
    }

    public function test_an_owner_without_a_related_record_is_loaded_as_null(): void
    {
        [ , , $carol ] = $employees = $this->create_employees();
        $this->badge->preload($employees);

        $actual = $this->count_queries(fn() => $carol->badge);

        $this->assertSame(0, $actual);
    }

    public function test_only_the_related_record_of_the_owner_is_attached(): void
    {
        [ $alice, $bob ] = $employees = $this->create_employees();

        $this->badge->preload($employees);

        $this->assertSame($alice->employee_id, $alice->badge?->employee_id);
        $this->assertSame($bob->employee_id, $bob->badge?->employee_id);
    }

    public function test_preload_skips_loaded_owners(): void
    {
        $employees = $this->create_employees();
        $this->badge->preload($employees);

        $actual = $this->count_queries(fn() => $this->badge->preload($employees));

        $this->assertSame(0, $actual);
    }

    public function test_preload_without_records_runs_no_query(): void
    {
        $actual = $this->count_queries(fn() => $this->badge->preload([]));

        $this->assertSame(0, $actual);
    }

    public function test_a_clone_is_not_loaded(): void
    {
        [ $alice ] = $employees = $this->create_employees();
        $this->badge->preload($employees);
        $clone = clone $alice;

        $actual = $this->count_queries(fn() => $clone->badge);

        $this->assertSame(1, $actual);
    }

    /**
     * @return Employee[] Alice and Bob have a badge, Carol doesn't.
     */
    private function create_employees(): array
    {
        $alice = Employee::from([ 'name' => "Alice" ])->save();
        $bob = Employee::from([ 'name' => "Bob" ])->save();
        $carol = Employee::from([ 'name' => "Carol" ])->save();
        Badge::from([ 'employee_id' => $alice->employee_id, 'number' => "B-001" ])->save();
        Badge::from([ 'employee_id' => $bob->employee_id, 'number' => "B-002" ])->save();
        $desk = Desk::from([ 'label' => "D-1" ])->save();
        DeskAssignment::from([ 'employee_id' => $alice->employee_id, 'desk_id' => $desk->desk_id ])->save();

        return [ $alice, $bob, $carol ];
    }

    /**
     * @param Closure(): mixed $closure
     */
    private function count_queries(Closure $closure): int
    {
        $telemetry = $this->employees->connection->telemetry;
        $before = $telemetry->execute_count;

        $closure();

        return $telemetry->execute_count - $before;
    }
}
