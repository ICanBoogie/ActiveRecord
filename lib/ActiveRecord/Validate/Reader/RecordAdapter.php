<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Validate\Reader;

use ICanBoogie\Accessor\AccessorTrait;
use ICanBoogie\ActiveRecord;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Validate\Reader\AbstractAdapter;

/**
 * Read values from an {@link ActiveRecord} instance.
 *
 * @property-read ActiveRecord $record
 */
class RecordAdapter extends AbstractAdapter
{
    /**
     * @uses get_record
     */
    use AccessorTrait;

    protected function get_record(): ActiveRecord
    {
        return $this->source;
    }

    public function __construct(ActiveRecord $source)
    {
        parent::__construct($source);
    }

    /**
     * @inheritdoc
     */
    public function read($name)
    {
        try {
            return $this->source->$name;
        } catch (PropertyNotDefined $e) {
            return null;
        }
    }
}
