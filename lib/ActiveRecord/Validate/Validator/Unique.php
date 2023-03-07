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
use ICanBoogie\Validate\Validator\ValidatorAbstract;
use RuntimeException;

/**
 * Validates that a value is unique in a table's column.
 */
class Unique extends ValidatorAbstract
{
    public const ALIAS = 'unique';
    public const DEFAULT_MESSAGE = '`{value}` is already used';

    /**
     * Specify the column to check, otherwise `attribute` is used.
     */
    public const OPTION_COLUMN = 'column';

    /**
     * @inheritdoc
     */
    public function validate($value, Context $context)
    {
        $column = $context->option(self::OPTION_COLUMN, $context->attribute)
            ?? throw new RuntimeException("Unable to resolve column from context option OPTION_COLUMN");
        $record = $this->resolve_record($context);
        $model = $record->model;
        $where = [ $column => $value ];
        $primary = $model->primary;

        if (!empty($record->$primary)) {
            $where['!' . $primary] = $record->$primary;
        }

        $a = $model->where($where)->all;

        return !$model->where($where)->exists;
    }

    /**
     * @inheritdoc
     */
    protected function get_params_mapping()
    {
        return [ self::OPTION_COLUMN ];
    }

    private function resolve_record(Context $context): ActiveRecord
    {
        $reader = $context->reader;

        if (!$reader instanceof RecordAdapter) {
            throw new \InvalidArgumentException(sprintf(
                "Expected `context.reader` to be an instance of `%s`.",
                RecordAdapter::class
            ));
        }

        return $reader->record;
    }
}
