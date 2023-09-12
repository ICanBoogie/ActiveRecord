<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\ActiveRecord\StaticModelProvider;
use ICanBoogie\Validate\ValidationErrors;
use LogicException;
use ReflectionException;
use Throwable;

use function array_keys;
use function is_array;
use function is_numeric;

/**
 * Active Record facilitates the creation and use of business objects whose data require persistent
 * storage via database.
 *
 * @method ValidationErrors validate() Validate the active record, returns an array of errors.
 *
 * @property-read Model $model The model managing the active record.
 * @uses self::get_model()
 * @property-read bool $is_new Whether the record is new or not.
 * @uses self::get_is_new()
 *
 * @template TKey of int|string|string[]
 */
abstract class ActiveRecord extends Prototyped
{
    public const SAVE_SKIP_VALIDATION = 'skip_validation';

    /**
     * Returns a new query.
     *
     * @return Query<static>
     *
     * @uses Model::query()
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
     * @uses Query::where()
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
     * @param ?Model<TKey,static> $model
     *     The model managing the active record. A {@link Model} instance can be specified as well as a model
     *     identifier. If `$model` is null, the model will be resolved with {@link StaticModelProvider} when required.
     */
    public function __construct(Model $model = null)
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
     * @param array<string, mixed> $options Save options.
     *
     * @return bool|int|mixed Primary key value of the active record, or a boolean if the primary key
     * is not a serial.
     *
     * @throws Throwable
     */
    public function save(array $options = []): mixed
    {
        if (empty($options[self::SAVE_SKIP_VALIDATION])) {
            $this->assert_is_valid();
        }

        $model = $this->get_model();
        $schema = $model->extended_schema;
        $properties = $this->alter_persistent_properties($this->to_array(), $schema);

        #
        # Multipart primary key
        #

        $primary = $model->primary;

        if (is_array($primary)) {
            return $model->insert($properties, [ 'on duplicate' => true ]);
        }

        #
        # Non auto-increment primary key, unless the key is inherited from parent model.
        #

        if (
            !$model->parent && $primary && isset($properties[$primary])
            && !$model->extended_schema->columns[$primary]->auto_increment
        ) {
            return $model->insert($properties, [ 'on duplicate' => true ]);
        }

        #
        # Auto-increment primary key
        #

        $key = null;

        if (isset($properties[$primary])) {
            $key = $properties[$primary];
            unset($properties[$primary]);
        }

        $rc = $model->save($properties, $key);

        if (is_numeric($rc)) {
            $rc = (int)$rc;
        }

        if ($key === null && $rc) {
            $this->update_primary_key($rc);
        }

        return $rc;
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
     * Updates primary key.
     *
     * @param int|non-empty-string|non-empty-array<non-empty-string> $primary_key
     */
    protected function update_primary_key(int|array|string $primary_key): void
    {
        $model = $this->get_model();
        $model_class = $model::class;
        $property = $model->primary
            ?? throw new LogicException("Unable to update primary key, model `$model_class` doesn't define one.");

        $this->$property = $primary_key;
    }

    /**
     * Deletes the active record using its model.
     *
     * @return bool `true` if the record was deleted, `false` otherwise.
     *
     * @throws LogicException in attempt to delete a record from a model which primary key is empty.
     */
    public function delete(): bool
    {
        $model = $this->get_model();
        $model_class = $model::class;
        $primary = $model->primary
            ?? throw new LogicException("Unable to delete record, model `$model_class` doesn't have a primary key");
        $key = $this->$primary
            ?? throw new LogicException("Unable to delete record, the primary key is not defined");

        return $model->delete($key);
    }
}
