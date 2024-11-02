<?php

namespace ICanBoogie\ActiveRecord\Validate\Reader;

use Error;
use ICanBoogie\ActiveRecord;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Validate\Reader\AbstractAdapter;

class RecordAdapter extends AbstractAdapter
{
    /**
     * Read values from an {@link ActiveRecord} instance.
     */
    public readonly ActiveRecord $record;

    public function __construct(ActiveRecord $source)
    {
        $this->record = $source;

        parent::__construct($source);
    }

    /**
     * @inheritdoc
     */
    public function read($name)
    {
        try {
            return $this->source->$name;
        } catch (PropertyNotDefined | Error $e) { // @phpstan-ignore-line
            return null;
        }
    }
}
