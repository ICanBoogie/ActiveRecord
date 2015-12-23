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

/**
 * Active Record facilitates the creation and use of business objects whose data require persistent
 * storage via database.
 *
 * @property-read Model $model The model managing the active record.
 * @property-read string $model_id The identifier of the model managing the active record.
 */
class ActiveRecord extends Prototyped
{
	/**
	 * The identifier of the model managing the record.
	 *
	 *  @var string
	 */
	const MODEL_ID = null;

	/**
	 * Model managing the active record.
	 *
	 * @var Model
	 */
	private $model;

	/**
	 * Identifier of the model managing the active record.
	 *
	 * Note: Due to a PHP bug (or feature), the visibility of the property MUST NOT be private.
	 * https://bugs.php.net/bug.php?id=40412
	 *
	 * @var string
	 */
	protected $model_id;

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
		if (!$model)
		{
			$model = static::MODEL_ID;
		}

		if (is_string($model))
		{
			$this->model_id = $model;
		}
		else if ($model instanceof Model)
		{
			$this->model = $model;
			$this->model_id = $model->id;
		}
		else
		{
			throw new \InvalidArgumentException("\$model must be an instance of ICanBoogie\\ActiveRecord\\Model or a model identifier. Given:" . (is_object($model) ? get_class($model) : gettype($model)));
		}
	}

	/**
	 * Removes the {@link $model} property.
	 *
	 * Properties whose value are instances of the {@link ActiveRecord} class are removed from the
	 * exported properties.
	 */
	public function __sleep()
	{
		$properties = parent::__sleep();

		unset($properties['model']);

		foreach (array_keys($properties) as $property)
		{
			if ($this->$property instanceof self)
			{
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
	 * Returns the model managing the active record.
	 *
	 * @return Model
	 */
	protected function get_model()
	{
		return $this->model
			?: $this->model = ActiveRecord\get_model($this->model_id);
	}

	/**
	 * Returns the identifier of the model managing the active record.
	 *
	 * @return string
	 */
	protected function get_model_id()
	{
		return $this->model_id;
	}

	/**
	 * Saves the active record using its model.
	 *
	 * @return int|bool Primary key value of the active record, or a boolean if the primary key
	 * is not a serial.
	 */
	public function save()
	{
		$model = $this->get_model();
		$schema = $model->extended_schema;
		$primary = $model->primary;
		$properties = $this->alter_persistent_properties($this->to_array(), $model);

		if (!$this->model->parent)
		{
			# removes the primary key from the properties.

			if (is_array($primary))
			{
				return $model->insert($properties, [ 'on duplicate' => true ]);
			}

			#

			$primary_column = $primary ? $schema[ $primary ] : null;

			if (isset($properties[ $primary ]) && !$primary_column->auto_increment)
			{
				return $model->insert($properties, [ 'on duplicate' => true ]);
			}
		}

		#

		$key = null;

		if (isset($properties[$primary]))
		{
			$key = $properties[$primary];
			unset($properties[$primary]);
		}

		$rc = $model->save($properties, $key);

		if ($key === null && $rc)
		{
			$this->$primary = $rc;
		}

		return $rc;
	}

	/**
	 * Unless it's an acceptable value for a column, columns with `null` values are discarded.
	 * This way, we don't have to define every properties before saving our active record.
	 *
	 * @param array $properties
	 * @param Model $model
	 *
	 * @return array The altered persistent properties
	 */
	protected function alter_persistent_properties(array $properties, Model $model)
	{
		$schema = $model->extended_schema;

		foreach ($properties as $identifier => $value)
		{
			if ($value !== null || (isset($schema[$identifier]) && $schema[$identifier]->null))
			{
				continue;
			}

			unset($properties[$identifier]);
		}

		return $properties;
	}

	/**
	 * Deletes the active record using its model.
	 *
	 * @return bool `true` if the record was deleted, `false` otherwise.
	 *
	 * @throws \Exception in attempt to delete a record from a model which primary key is empty.
	 */
	public function delete()
	{
		$model = $this->model;
		$primary = $model->primary;

		if (!$primary)
		{
			throw new \LogicException("Unable to delete record, primary key is empty.");
		}

		return $model->delete($this->$primary);
	}
}
