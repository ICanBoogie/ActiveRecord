<?php

namespace Test\ICanBoogie;

use Closure;
use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\ModelProvider;
use ICanBoogie\ActiveRecord\ModelProviderWithClosure;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use Test\ICanBoogie\Acme\Node;
use Test\ICanBoogie\ActiveRecordTest\Composite;
use Test\ICanBoogie\ActiveRecordTest\Natural;
use Test\ICanBoogie\ActiveRecordTest\NoProperties;
use Test\ICanBoogie\ActiveRecordTest\Nullable;
use Test\ICanBoogie\ActiveRecordTest\ValidateCase;
use Test\ICanBoogie\ActiveRecordTest\Validated;

use function serialize;
use function uniqid;

#[Group("record")]
#[Group("db")]
final class ActiveRecordTest extends DbTestCase
{
    /**
     * @var Model<Node>
     */
    private Model $model;

    protected function setUp(): void
    {
        $models = Fixtures::only_models('nodes');

        $this->model = $models->model_for_record(Node::class);
        $this->model->install();

        StaticModelProvider::set(fn() => new ModelProviderWithClosure(fn() => $this->model));
    }

    protected function tearDown(): void
    {
        StaticModelProvider::reset();

        parent::tearDown();
    }

    /**
     * Builds and installs a model for the given record class.
     *
     * @template T of ActiveRecord
     *
     * @param class-string<T> $record_class
     * @param Closure(SchemaBuilder): SchemaBuilder $schema_builder
     *
     * @return Model<T>
     */
    private function model_with(string $record_class, Closure $schema_builder): Model
    {
        $config = Fixtures::with_main_connection(new ConfigBuilder())
            ->add_record(
                record_class: $record_class,
                schema_builder: $schema_builder,
            )
            ->build();

        $connections = new ConnectionCollection($config->connections);
        $models = new ModelCollection($connections, $config->models);

        $model = $models->model_for_record($record_class);
        $model->install();

        return $model;
    }

    /**
     * @return Model<Composite>
     */
    private function composite_model(): Model
    {
        return $this->model_with(Composite::class, fn(SchemaBuilder $schema) => $schema
            ->add_integer('a', primary: true)
            ->add_integer('b', primary: true)
            ->add_character('name'));
    }

    public function test_query(): void
    {
        $actual = Node::query();

        $this->assertSame($this->model, $actual->model);
    }

    public function test_where(): void
    {
        $actual = Node::where([ 'title' => "foo" ]);

        $this->assertSame($this->model, $actual->model);
        $this->assertSame([ "foo" ], $actual->conditions_args);
    }

    public function test_get_model(): void
    {
        $sut = new Node();

        $this->assertSame($this->model, $sut->model);
    }

    public function test_should_use_provided_model(): void
    {
        $record = new Node($this->model);

        $this->assertSame($this->model, $record->model);
    }

    public function test_model_is_resolved_with_resolver(): void
    {
        $provider = $this->createMock(ModelProvider::class);
        $provider
            ->expects($this->exactly(1))
            ->method('model_for_record')
            ->with(Node::class)
            ->willReturn($this->model);

        StaticModelProvider::set(fn() => $provider);

        $record = new Node();

        $this->assertSame($this->model, $record->model);
    }

    public function test_is_new(): void
    {
        $sut = new Node();
        $sut->title = "madonna";

        $this->assertTrue($sut->is_new);

        $sut->save();

        $this->assertFalse($sut->is_new);
    }

    public function test_is_new_with_multi_column_primary_key(): void
    {
        $sut = new Composite($this->composite_model());

        $this->assertTrue($sut->is_new);

        $sut->a = 1;

        $this->assertTrue($sut->is_new, "Only part of the primary key is defined");

        $sut->b = 2;

        $this->assertFalse($sut->is_new);
    }

    public function test_primary_key_value(): void
    {
        $sut = new Node();

        $this->assertNull($sut->primary_key_value);

        $sut->nid = 123;

        $this->assertSame(123, $sut->primary_key_value);
    }

    public function test_primary_key_value_with_multi_column_primary_key(): void
    {
        $sut = new Composite($this->composite_model());

        $this->assertSame([ null, null ], $sut->primary_key_value);

        $sut->a = 1;
        $sut->b = 2;

        $this->assertSame([ 1, 2 ], $sut->primary_key_value);
    }

    public function test_save(): void
    {
        $sut = new Node();
        $sut->title = "madonna";
        $this->assertSame($sut, $sut->save());

        $nid = $sut->nid;
        $this->assertGreaterThan(0, $nid);
        $this->assertEquals($nid, $sut->primary_key_value);

        $sut->title = "madonna 2";
        $this->assertSame($sut, $sut->save());

        $this->assertEquals($nid, $sut->nid);
        $this->assertEquals($nid, $sut->primary_key_value);
        $this->assertSame(1, $this->model->query()->count);

        $found = $this->model->find($nid);
        $this->assertSame("madonna 2", $found->title);
    }

    public function test_save_validates(): void
    {
        $model = $this->model_with(Validated::class, fn(SchemaBuilder $schema) => $schema
            ->add_serial('id', primary: true)
            ->add_character('name'));

        $record = new Validated($model);
        $record->name = 'ab';

        try {
            $record->save();
            $this->fail("Expected RecordNotValid");
        } catch (RecordNotValid $e) {
            $this->assertSame($record, $e->record);
            $this->assertArrayHasKey('name', $e->errors->to_array());
        }

        $this->assertTrue($record->is_new);
        $this->assertSame(0, $model->query()->count);
    }

    public function test_save_skips_validation(): void
    {
        $model = $this->model_with(Validated::class, fn(SchemaBuilder $schema) => $schema
            ->add_serial('id', primary: true)
            ->add_character('name'));

        $record = new Validated($model);
        $record->name = 'ab';

        $this->assertSame($record, $record->save(skip_validation: true));

        $id = $record->id;
        $this->assertIsInt($id);

        $found = $model->find($id);
        $this->assertSame('ab', $found->name);
    }

    public function test_save_with_no_properties(): void
    {
        $model = $this->model_with(NoProperties::class, fn(SchemaBuilder $schema) => $schema
            ->add_serial('id', primary: true)
            ->add_character('name'));

        $record = new NoProperties($model);
        $record->name = 'madonna';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs("No properties to save");

        $record->save();
    }

    public function test_save_discards_null_values_of_non_nullable_columns(): void
    {
        $model = $this->model_with(Nullable::class, fn(SchemaBuilder $schema) => $schema
            ->add_serial('id', primary: true)
            ->add_character('name', default: 'anonymous')
            ->add_character('nickname', null: true));

        $record = new Nullable($model);
        $record->save();

        $found = $model->find($record->id);
        $this->assertSame('anonymous', $found->name, "null was discarded, the default value was used");
        $this->assertNull($found->nickname);

        $record->name = 'madonna';
        $record->nickname = 'queen';
        $record->save();

        $record->name = null;
        $record->nickname = null;
        $record->save();

        $found = $model->find($record->id);
        $this->assertSame('madonna', $found->name, "null was discarded, the value was not updated");
        $this->assertNull($found->nickname, "null is accepted by the column");
    }

    public function test_save_with_multi_column_primary_key(): void
    {
        $model = $this->composite_model();

        $record = new Composite($model);
        $record->a = 1;
        $record->b = 2;
        $record->name = 'madonna';

        $this->assertSame($record, $record->save());

        $found = $model->where([ 'a' => 1, 'b' => 2 ])->one;
        assert($found instanceof Composite);
        $this->assertSame('madonna', $found->name);

        $record->name = 'madonna 2';
        $this->assertSame($record, $record->save());

        $found = $model->where([ 'a' => 1, 'b' => 2 ])->one;
        assert($found instanceof Composite);
        $this->assertSame('madonna 2', $found->name);
        $this->assertSame(1, $model->query()->count);
    }

    public function test_save_with_non_serial_primary_key(): void
    {
        $model = $this->model_with(Natural::class, fn(SchemaBuilder $schema) => $schema
            ->add_character('code', primary: true)
            ->add_character('name'));

        $record = new Natural($model);
        $record->code = 'alpha';
        $record->name = 'madonna';

        $this->assertSame($record, $record->save());

        $found = $model->find('alpha');
        $this->assertSame('madonna', $found->name);

        $record->name = 'madonna 2';
        $this->assertSame($record, $record->save());

        $found = $model->find('alpha');
        $this->assertSame('madonna 2', $found->name);
        $this->assertSame(1, $model->query()->count);
    }

    public function test_delete(): void
    {
        $record = new Node($this->model);
        $record->title = "madonna";
        $record->save();

        $other = new Node($this->model);
        $other->title = "other";
        $other->save();

        $record->delete();

        $this->assertSame(1, $this->model->query()->count);
        $this->assertFalse($this->model->query()->exists($record->nid));
        $this->assertTrue($this->model->query()->exists($other->nid));
    }

    public function test_delete_missing_primary(): void
    {
        $record = new Node($this->model);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs("Unable to delete record, the primary key is not defined");

        $record->delete();
    }

    public function test_delete_with_multi_column_primary_key(): void
    {
        $model = $this->composite_model();

        $record = new Composite($model);
        $record->a = 1;
        $record->b = 2;
        $record->name = 'madonna';
        $record->save();

        $other = new Composite($model);
        $other->a = 1;
        $other->b = 3;
        $other->name = 'other';
        $other->save();

        $record->delete();

        $this->assertSame(0, $model->where([ 'a' => 1, 'b' => 2 ])->count);
        $this->assertSame(1, $model->where([ 'a' => 1, 'b' => 3 ])->count);
    }

    public function test_delete_with_multi_column_primary_key_partially_defined(): void
    {
        $record = new Composite($this->composite_model());
        $record->a = 1;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs("Unable to delete record, the primary key is not defined");

        $record->delete();
    }

    public function test_delete_without_primary_key(): void
    {
        $model = $this->model_with(Natural::class, fn(SchemaBuilder $schema) => $schema
            ->add_character('code')
            ->add_character('name'));

        $record = new Natural($model);
        $record->code = 'alpha';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs(
            "Unable to delete record, model `" . Model::class . "` doesn't have a primary key"
        );

        $record->delete();
    }

    public function test_sleep_should_remove_model_and_computed_properties(): void
    {
        $record = new Node($this->model);
        $record->title = "madonna";

        $properties = $record->__sleep();

        $this->assertArrayNotHasKey('model', $properties);
        $this->assertArrayNotHasKey('is_new', $properties);
        $this->assertArrayNotHasKey('primary_key_value', $properties);
        $this->assertArrayHasKey('title', $properties);
    }

    public function test_serialize_should_remove_model_info(): void
    {
        $record = new Node($this->model);
        $record->title = "madonna";
        $record->save();

        $serialized_record = serialize($record);

        $this->assertStringNotContainsString('"model"', $serialized_record);
        $this->assertStringNotContainsString('"model_id"', $serialized_record);

        $actual = unserialize($serialized_record);

        $this->assertInstanceOf(Node::class, $actual);
        $this->assertSame($record->nid, $actual->nid);
        $this->assertSame("madonna", $actual->title);
        $this->assertSame($this->model, $actual->model, "the model is resolved again");
    }

    public function test_debug_info_should_exclude_model(): void
    {
        $record = new Node($this->model);
        $record->title = uniqid();

        $array = $record->__debugInfo();

        $this->assertArrayNotHasKey('model', $array);
        $this->assertArrayNotHasKey("\0" . ActiveRecord::class . "\0model", $array);
        $this->assertArrayHasKey('title', $array);
    }

    #[Group("validate")]
    public function test_validate(): void
    {
        $record = new ValidateCase($this->model);

        try {
            $record->save();
            $this->fail("Expected RecordNotValid");
        } catch (RecordNotValid $e) {
            $errors = $e->errors->to_array();

            $this->assertArrayNotHasKey('id', $errors);
            $this->assertArrayHasKey('name', $errors);
            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('timezone', $errors);
        }
    }
}
