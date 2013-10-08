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
 * Active Record faciliates the creation and use of business objects whose data require persistent
 * storage via database.
 *
 * @property-read Model $model The model managing the active record.
 * @property-read string $model_id The identifier of the model managing the active record.
 */
class ActiveRecord extends \ICanBoogie\Object
{
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
	 * @param string|Model $model The model managing the active record. A {@link Model}
	 * instance can be specified as well as a model identifier. If a model identifier is
	 * specified, the model is resolved when the {@link $model} property is accessed.
	 *
	 * @throws \InvalidArgumentException if $model is neither a model identifier nor a
	 * {@link Model} instance.
	 */
	public function __construct($model)
	{
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
			throw new \InvalidArgumentException("\$model must be an instance of ICanBoogie\ActiveRecord\Model or a model identifier. Given:" . (is_object($model) ? get_class($model) : gettype($model)));
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

		foreach ($properties as $property => $dummy)
		{
			if ($this->$property instanceof self)
			{
				unset($properties[$property]);
			}
		}

		return $properties;
	}

	/**
	 * Removes the {@link $model} and {@link $model_id} properties.
	 */
	public function to_array()
	{
		$array = parent::to_array();

		unset($array['model']);
		unset($array['model_id']);

		return $array;
	}

	/**
	 * Returns the model managing the active record.
	 *
	 * This getter is used when the model has been provided as a string during construct.
	 *
	 * @return Model
	 */
	protected function volatile_get_model()
	{
		if (!$this->model)
		{
			$this->model = ActiveRecord\get_model($this->model_id);
		}

		return $this->model;
	}

	/**
	 * Alias to {@link volatile_get_model}.
	 *
	 * @deprecated
	 *
	 * @see volatile_get_model
	 */
	protected function volatile_get__model()
	{
		return $this->volatile_get_model();
	}

	/**
	 * Returns the identifier of the model managing the active record.
	 *
	 * The getter is used to provide read-only access to the property.
	 *
	 * @return string
	 */
	protected function volatile_get_model_id()
	{
		return $this->model_id;
	}

	/**
	 * Alias to {@link volatile_get_model_id}.
	 *
	 * @deprecated
	 *
	 * @see volatile_get_model_id
	 */
	protected function volatile_get__model_id()
	{
		return $this->volatile_get_model_id();
	}

	/**
	 * Saves the active record using its model.
	 *
	 * @return int Primary key value of the active record.
	 */
	public function save()
	{
		$model = $this->volatile_get__model();
		$properties = $this->to_array();
		$properties = $this->alter_persistent_properties($properties, $model);

		# removes the primary key from the properties.

		$key = null;
		$primary = $model->primary;

		if (is_array($primary))
		{
			$rc = $model->insert($properties, array('on duplicate' => true));
		}
		else
		{
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
		}

		return $rc;
	}

	/**
	 * Unless it's an acceptable value for a column, columns with `null` values are discarted.
	 * This way, we don't have to define every properties before saving our active record.
	 *
	 * @param array $properties
	 *
	 * @return array The altered persistent properties
	 */
	protected function alter_persistent_properties(array $properties, Model $model)
	{
		$schema = $model->extended_schema;

		foreach ($properties as $identifier => $value)
		{
			if ($value !== null || (isset($schema['fields'][$identifier]) && !empty($schema['fields'][$identifier]['null'])))
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
	 */
	public function delete()
	{
		$model = $this->volatile_get__model();
		$primary = $model->primary;

		return $model->delete($this->$primary);
	}
}

namespace ICanBoogie\ActiveRecord;

/**
 * Generic Active Record exception class.
 */
class ActiveRecordException extends \Exception
{

}