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
use function in_array;
use function is_array;

/**
 * Active Record facilitates the creation and use of business objects whose data require persistent
 * storage via a database.
 *
 * @method ValidationErrors validate() Validate the active record, returns an array of errors.
 */
abstract class ActiveRecord extends Prototyped
{
    /**
     * Returns the model managing the active record.
     *
     * @return Model<static>
     */
    final public static function model(): Model
    {
        return StaticModelProvider::model_for_record(static::class);
    }

    /**
     * Returns a new query.
     *
     * @return Query<static>
     *
     * @see Model::query()
     */
    final public static function query(): Query
    {
        return self::model()->query();
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
     * @return scalar|scalar[]
     */
    public mixed $primary_key_value
    {
        get {
            $primary = static::model()->extended_schema->primary;

            if (is_array($primary)) {
                $actual = [];

                foreach ($primary as $property) {
                    $actual[] = $this->$property ?? null;
                }

                return $actual;
            }

            // The primary key might not be initialized yet.
            return $this->$primary ?? null;
        }
    }

    /**
     * Removes computed properties.
     *
     * Properties whose values are instances of the {@see ActiveRecord} class are removed from the
     * exported properties.
     *
     * @return array<non-empty-string, mixed>
     *
     * @throws ReflectionException
     */
    public function __sleep() // @phpstan-ignore-line
    {
        $properties = parent::__sleep();

        // @phpstan-ignore-next-line
        unset($properties['is_new']);
        // @phpstan-ignore-next-line
        unset($properties['primary_key_value']);

        foreach (array_keys($properties) as $property) {
            if ($this->$property instanceof self) {
                unset($properties[$property]);
            }
        }

        return $properties;
    }

    /**
     * Whether the record is new or not.
     */
    public bool $is_new {
        get {
            $primary = static::model()->primary;

            if (is_array($primary)) {
                if (array_any($primary, fn($property) => empty($this->$property))) {
                    return true;
                }
            } elseif (empty($this->$primary)) {
                return true;
            }

            return false;
        }
    }

    /**
     * Saves the active record using its model.
     *
     * @return $this
     *
     * @throws Throwable
     */
    public function save(bool $skip_validation = false): static
    {
        if (!$skip_validation) {
            $this->assert_is_valid();
        }

        $model = static::model();
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

            return $this;
        }

        #
        # Non auto-increment primary key, unless the key is inherited from parent model.
        #

        if (
            !$model->parent && $primary && isset($properties[$primary])
            && !$model->extended_schema->columns[$primary] instanceof Serial
        ) {
            $model->insert($properties, upsert: true);

            return $this;
        }

        #
        # Serial primary key
        #

        $id = null;

        if ($primary && isset($properties[$primary])) {
            $id = $properties[$primary];
            unset($properties[$primary]);
            assert(is_numeric($id));
        }

        // @phpstan-ignore-next-line
        $rc = $model->save($properties, $id);

        if ($id === null && $primary) {
            $this->$primary = $rc;
        }

        return $this;
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
        $model = static::model();
        $model_class = $model::class;
        $model->primary
            ?? throw new LogicException("Unable to delete record, model `$model_class` doesn't have a primary key");
        $key = $this->primary_key_value;

        if ($key === null || (is_array($key) && in_array(null, $key, true))) {
            throw new LogicException("Unable to delete record, the primary key is not defined");
        }

        // @phpstan-ignore-next-line
        $model->delete($key);
    }
}
