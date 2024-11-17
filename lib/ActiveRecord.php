<?php

namespace ICanBoogie;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use ICanBoogie\Validate\ValidationErrors;
use LogicException;
use ReflectionException;
use Throwable;

use function array_keys;
use function is_array;

/**
 * Active Record facilitates the creation and use of business objects whose data require persistent
 * storage via a database.
 *
 * @method ValidationErrors validate() Validate the active record, returns an array of errors.
 *
 * @see self::get_model()
 * @property-read Model $model The model managing the active record.
 * @see self::get_is_new()
 * @property-read bool $is_new Whether the record is new or not.
 * @see self::get_primary_key_value()
 * @property-read TKey $primary_key_value The value of the primary key.
 *
 * @template TKey of scalar|scalar[]
 */
abstract class ActiveRecord extends Prototyped
{
    /**
     * Returns a new query.
     *
     * @return Query<static>
     *
     * @see Model::query()
     */
    final public static function query(): Query
    {
        return StaticModelProvider::model_for_record(static::class)->query();
    }

    /**
     * Returns a new query with the WHERE clause initialized with the provided conditions and arguments.
     *
     * @param mixed ...$conditions_and_args
     *
     * @return Query<static>
     *
     * @see Query::where()
     */
    final public static function where(...$conditions_and_args): Query
    {
        return self::query()->where(...$conditions_and_args);
    }

    /**
     * Model managing the active record.
     *
     * @var Model<TKey, static>
     */
    private Model $model;

    /**
     * @return Model<TKey, static>
     */
    protected function get_model(): Model
    {
        return $this->model
            ??= StaticModelProvider::model_for_record($this::class);
    }

    /**
     * @return mixed&TKey
     */
    protected function get_primary_key_value(): mixed
    {
        $model = $this->get_model();
        $primary = $model->extended_schema->primary;

        if (is_array($primary)) {
            $actual = [];

            foreach ($primary as $property) {
                $actual[] = $this->$property;
            }

            return $actual;
        }

        return $this->$primary;
    }

    /**
     * @param ?Model<TKey, static> $model
     *     The model managing the active record. A {@link Model} instance can be specified as well as a model
     *     identifier. If `$model` is null, the model will be resolved with {@link StaticModelProvider} when required.
     */
    public function __construct(?Model $model = null)
    {
        if ($model) {
            $this->model = $model;
        }
    }

    /**
     * Removes the {@link $model} property.
     *
     * Properties whose value are instances of the {@link ActiveRecord} class are removed from the
     * exported properties.
     *
     * @return array<non-empty-string, mixed>
     *
     * @throws ReflectionException
     */
    public function __sleep() // @phpstan-ignore-line
    {
        $properties = parent::__sleep();

        /** @phpstan-ignore-next-line */
        unset($properties['model']);

        foreach (array_keys($properties) as $property) {
            if ($this->$property instanceof self) {
                unset($properties[$property]);
            }
        }

        return $properties;
    }

    /**
     * Removes `model` from the output.
     *
     * @return array<non-empty-string, mixed>
     */
    public function __debugInfo(): array
    {
        $array = (array)$this;

        unset($array["\0" . __CLASS__ . "\0model"]);

        return $array;
    }

    /**
     * Whether the record is new or not.
     */
    protected function get_is_new(): bool
    {
        $primary = $this->get_model()->primary;

        if (is_array($primary)) {
            foreach ($primary as $property) {
                if (empty($this->$property)) {
                    return true;
                }
            }
        } elseif (empty($this->$primary)) {
            return true;
        }

        return false;
    }

    /**
     * Saves the active record using its model.
     *
     * @throws Throwable
     */
    public function save(bool $skip_validation = false): void
    {
        if (!$skip_validation) {
            $this->assert_is_valid();
        }

        $model = $this->get_model();
        $schema = $model->extended_schema;
        // @phpstan-ignore-next-line
        $properties = $this->alter_persistent_properties($this->to_array(), $schema);

        if (count($properties) == 0) {
            throw new LogicException("No properties to save");
        }

        #
        # Multi-column primary key
        #

        $primary = $model->primary;

        if (is_array($primary)) {
            $model->insert($properties, upsert: true);

            return;
        }

        #
        # Non auto-increment primary key, unless the key is inherited from parent model.
        #

        if (
            !$model->parent && $primary && isset($properties[$primary])
            && !$model->extended_schema->columns[$primary] instanceof Serial
        ) {
            $model->insert($properties, upsert: true);

            return;
        }

        #
        # Serial primary key
        #

        $id = null;

        if (isset($properties[$primary])) {
            $id = $properties[$primary];
            unset($properties[$primary]);
            assert(is_numeric($id));
        }

        // @phpstan-ignore-next-line
        $rc = $model->save($properties, $id);

        if ($id === null) {
            $this->$primary = $rc;
        }
    }

    /**
     * Assert that a record is valid.
     *
     * @throws RecordNotValid if the record is not valid.
     */
    public function assert_is_valid(): void
    {
        $errors = $this->validate();

        if (count($errors)) {
            throw new RecordNotValid($this, $errors);
        }
    }

    /**
     * Creates validation rules.
     *
     * @return array<string, mixed>
     */
    public function create_validation_rules(): array
    {
        return [];
    }

    /**
     * Unless it's an acceptable value for a column, columns with `null` values are discarded.
     * This way, we don't have to define every property before saving our active record.
     *
     * @param array<non-empty-string, mixed> $properties
     * @param Schema $schema The model's extended schema.
     *
     * @return array<non-empty-string, mixed> The altered persistent properties
     */
    protected function alter_persistent_properties(array $properties, Schema $schema): array
    {
        foreach ($properties as $identifier => $value) {
            if ($value !== null || ($schema->has_column($identifier) && $schema->columns[$identifier]->null)) {
                continue;
            }

            unset($properties[$identifier]);
        }

        return $properties;
    }

    /**
     * Deletes the active record using its model.
     *
     * @throws LogicException in an attempt to delete a record from a model which primary key is empty.
     */
    public function delete(): void
    {
        $model = $this->get_model();
        $model_class = $model::class;
        $primary = $model->primary
            ?? throw new LogicException("Unable to delete record, model `$model_class` doesn't have a primary key");
        $key = $this->$primary
            ?? throw new LogicException("Unable to delete record, the primary key is not defined");

        $model->delete($key);
    }
}
