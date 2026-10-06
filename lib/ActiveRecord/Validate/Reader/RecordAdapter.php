<?php

namespace ICanBoogie\ActiveRecord\Validate\Reader;

use Error;
use ICanBoogie\ActiveRecord;
use ICanBoogie\PropertyNotDefined;
use ICanBoogie\Validate\Reader\AbstractAdapter;

class RecordAdapter extends AbstractAdapter
{
    /**
     * Read values from an {@see ActiveRecord} instance.
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
    public function read(string $name): mixed
    {
        try {
            return $this->source->$name;
        } catch (PropertyNotDefined | Error $e) {
            return null;
        }
    }
}
