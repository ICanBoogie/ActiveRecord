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
use ICanBoogie\ActiveRecord\RecordNotValid;
use ICanBoogie\ActiveRecord\Schema;
use ICanBoogie\Validate\ValidationErrors;
use LogicException;

use function get_debug_type;
use function sprintf;

/**
 * Active Record facilitates the creation and use of business objects whose data require persistent
 * storage via database.
 *
 * @method ValidationErrors validate() Validate the active record, returns an array of errors.
 *
 * @property-read Model $model The model managing the active record.
 * @property-read string $model_id The identifier of the model managing the active record.
 * @property-read bool $is_new Whether the record is new or not.
 */
class ActiveRecord extends Prototyped
{
    public const SAVE_SKIP_VALIDATION = 'skip_validation';

    /**
     * The identifier of the model managing the record.
     */
    public const MODEL_ID = null;

    /**
     * Model managing the active record.
     *
     * @var Model|null
     */
    private $model;

    protected function get_model(): Model
    {
        return $this->model
            ?: $this->model = ActiveRecord\get_model($this->model_id);
    }

    /**
     * Identifier of the model managing the active record.
     *
     * Note: Due to a PHP bug (or feature), the visibility of the property MUST NOT be private.
     * https://bugs.php.net/bug.php?id=40412
     *
     * @var string|null
     */
    protected $model_id;

    protected function get_model_id(): ?string
    {
        return $this->model_id;
    }

    /**
     * Initializes the {@link $model} and {@link $model_id} properties.
     *
     * @param string|Model|null $model The model managing the active record. A {@link Model}
     * instance can be specified as well as a model identifier. If a model identifier is
     * specified, the model is resolved when the {@link $model} property is accessed. If `$model`
     * is empty, the identifier of the model is read from the {@link MODEL_ID} class constant.
     *
     * @throws \InvalidArgumentException if $model is neither a model identifier nor a
     * {@link Model} instance.
     */
    public function __construct($model = null)
    {
        if (!$model) {
            $model = static::MODEL_ID;
        }

        if (\is_string($model)) {
            $this->model_id = $model;
        } else {
            if ($model instanceof Model) {
                $this->model = $model;
                $this->model_id = $model->id;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    "\$model must be an instance of '%s' or a model identifier. Given: %s.",
                    Model::class,
                    get_debug_type($model)
                ));
            }
        }
    }

    /**
     * Removes the {@link $model} property.
     *
     * Properties whose value are instances of the {@link ActiveRecord} class are removed from the
     * exported properties.
     */
    public function __sleep(): array
    {
        $properties = parent::__sleep();

        unset($properties['model']);

        foreach (\array_keys($properties) as $property) {
            if ($this->$property instanceof self) {
                unset($properties[$property]);
            }
        }

        return $properties;
    }

    /**
     * Removes `model` from the output, since `model_id` is good enough to figure which model
     * is used.
     *
     * @return array
     */
    public function __debugInfo()
    {
        $array = (array) $this;

        unset($array["\0" . __CLASS__ . "\0model"]);

        return $array;
    }

    /**
     * Whether the record is new or not.
     *
     * @return bool
     */
    protected function get_is_new(): bool
    {
        $primary = $this->get_model()->primary;

        if (\is_array($primary)) {
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
     * @return bool|int Primary key value of the active record, or a boolean if the primary key
     * is not a serial.
     */
    public function save(array $options = [])
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

        if (\is_array($primary)) {
            return $model->insert($properties, [ 'on duplicate' => true ]);
        }

        #
        # Non auto-increment primary key, unless the key is inherited from parent model.
        #

        if (
            !$model->parent && $primary && isset($properties[$primary])
            && !$model->extended_schema[$primary]->auto_increment
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
     * This way, we don't have to define every properties before saving our active record.
     *
     * @param array<string, mixed> $properties
     * @param Schema $schema The model's extended schema.
     *
     * @return array<string, mixed> The altered persistent properties
     */
    protected function alter_persistent_properties(array $properties, Schema $schema): array
    {
        foreach ($properties as $identifier => $value) {
            if ($value !== null || (isset($schema[$identifier]) && $schema[$identifier]->null)) {
                continue;
            }

            unset($properties[$identifier]);
        }

        return $properties;
    }

    /**
     * Updates primary key.
     *
     * @param array|string|int $primary_key
     */
    protected function update_primary_key($primary_key): void
    {
        $model = $this->model;
        $property = $model->primary;

        if (!$property) {
            throw new LogicException("Unable to update primary key, model `$model->id` doesn't define one.");
        }

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
        $model = $this->model;
        $primary = $model->primary;

        if (!$primary) {
            throw new LogicException("Unable to delete record, model `$model->id` doesn't have a primary key.");
        }

        return $model->delete($this->$primary);
    }
}
