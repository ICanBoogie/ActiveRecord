<?php

namespace ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Sample active record test case.
 *
 * @property-read int|null $id
 */
class Sample extends ActiveRecord
{
	const MODEL_ID = 'sample';

	/**
	 * @var int|null
	 */
	private $id;

	protected function get_id(): ?int
	{
		return $this->id;
	}

	public $reverse;

	/**
	 * Reverses the value of the `reverse` property.
	 *
	 * @inheritdoc
	 */
	protected function alter_persistent_properties(array $properties, ActiveRecord\Schema $schema): array
	{
		return array_merge(parent::alter_persistent_properties($properties, $schema), [

			'reverse' => strrev($this->reverse)

		]);
	}

	/**
	 * @param int $id
	 */
	protected function update_primary_key($id): void
	{
		$this->id = $id;
	}
}
