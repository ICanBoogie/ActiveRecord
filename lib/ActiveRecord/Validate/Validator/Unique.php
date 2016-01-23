<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\Validator;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Validate\Reader\RecordAdapter;
use ICanBoogie\Validate\Context;
use ICanBoogie\Validate\Validator\AbstractValidator;

/**
 * Validates that a value is unique in a table's column.
 */
class Unique extends AbstractValidator
{
	const ALIAS = 'unique';
	const DEFAULT_MESSAGE = '`{value}` is already used';

	/**
	 * Specify the column to check, otherwise `attribute` is used.
	 */
	const OPTION_COLUMN = 'column';

	/**
	 * @inheritdoc
	 */
	public function validate($value, Context $context)
	{
		$column = $context->option(self::OPTION_COLUMN, $context->attribute);
		$record = $this->resolve_record($context);
		$model = $record->model;
		$where = [ $column => $value ];
		$primary = $model->primary;

		if (!empty($record->$primary))
		{
			$where['!' . $primary] = $record->$primary;
		}

		return !$model->where($where)->exists;
	}

	/**
	 * @inheritdoc
	 */
	protected function get_params_mapping()
	{
		return [ self::OPTION_COLUMN ];
	}

	/**
	 * @param Context $context
	 *
	 * @return ActiveRecord
	 */
	protected function resolve_record(Context $context)
	{
		$reader = $context->reader;

		if (!$reader instanceof RecordAdapter)
		{
			throw new \InvalidArgumentException(sprintf("Expected `context.reader` to be an instance of `%s`.", RecordAdapter::class));
		}

		return $reader->record;
	}
}
