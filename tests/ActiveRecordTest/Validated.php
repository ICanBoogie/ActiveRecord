<?php

namespace Test\ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Record with a serial primary key and validation rules.
 */
final class Validated extends ActiveRecord
{
    public int $id;

    public string $name;

    public function create_validation_rules(): array
    {
        return parent::create_validation_rules() + [

            'name' => 'required|min-length:3'

        ];
    }
}
