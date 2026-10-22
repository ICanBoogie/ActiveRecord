<?php

namespace Test\ICanBoogie\ActiveRecord;

use ICanBoogie\ActiveRecord\HasOneRelation;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\HasOne\Badge;
use Test\ICanBoogie\Acme\HasOne\Desk;
use Test\ICanBoogie\Acme\HasOne\DeskAssignment;
use Test\ICanBoogie\Acme\HasOne\Employee;
use Test\ICanBoogie\DbTestCase;
use Test\ICanBoogie\Fixtures;

#[Group("db")]
final class HasOneRelationTest extends DbTestCase
{
    /**
     * @var Model<Employee>
     */
    private Model $employees;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('employees', 'badges', 'desks', 'desk_assignments');

        new ModelInstaller($models)->install();

        $this->employees = $models->model_for_record(Employee::class);

        StaticModelProvider::set(fn() => $models);
    }

    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    public function test_relations(): void
    {
        $relations = $this->employees->relations;

        $badge = $relations->get('badge');
        $this->assertInstanceOf(HasOneRelation::class, $badge);
        $this->assertSame(Badge::class, $badge->related);
        $this->assertSame('employee_id', $badge->local_key);
        $this->assertSame('employee_id', $badge->foreign_key);
        $this->assertNull($badge->through);
        $this->assertNull($badge->through_key);

        $desk = $relations->get('desk');
        $this->assertInstanceOf(HasOneRelation::class, $desk);
        $this->assertSame(Desk::class, $desk->related);
        $this->assertSame('employee_id', $desk->local_key);
        $this->assertSame('employee_id', $desk->foreign_key);
        $this->assertSame(DeskAssignment::class, $desk->through);
        $this->assertSame('desk_id', $desk->through_key);
    }

    public function test_getter_returns_the_related_record(): void
    {
        $alice = Employee::from([ 'name' => "Alice" ])->save();
        Badge::from([ 'employee_id' => $alice->employee_id, 'number' => "B-001" ])->save();
        $desk = Desk::from([ 'label' => "D-42" ])->save();
        DeskAssignment::from([ 'employee_id' => $alice->employee_id, 'desk_id' => $desk->desk_id ])->save();

        $actual_badge = $alice->badge;
        $actual_desk = $alice->desk;

        $this->assertInstanceOf(Badge::class, $actual_badge);
        $this->assertSame("B-001", $actual_badge->number);
        $this->assertInstanceOf(Desk::class, $actual_desk);
        $this->assertSame("D-42", $actual_desk->label);
    }

    public function test_getter_returns_null_without_related_record(): void
    {
        $bob = Employee::from([ 'name' => "Bob" ])->save();

        $actual_badge = $bob->badge;
        $actual_desk = $bob->desk;

        $this->assertNull($actual_badge);
        $this->assertNull($actual_desk);
    }

    public function test_getter_only_returns_the_record_of_the_owner(): void
    {
        $alice = Employee::from([ 'name' => "Alice" ])->save();
        $bob = Employee::from([ 'name' => "Bob" ])->save();
        Badge::from([ 'employee_id' => $bob->employee_id, 'number' => "B-002" ])->save();
        $desk = Desk::from([ 'label' => "D-43" ])->save();
        DeskAssignment::from([ 'employee_id' => $bob->employee_id, 'desk_id' => $desk->desk_id ])->save();

        $actual_badge = $alice->badge;
        $actual_desk = $alice->desk;

        $this->assertNull($actual_badge);
        $this->assertNull($actual_desk);
    }
}
