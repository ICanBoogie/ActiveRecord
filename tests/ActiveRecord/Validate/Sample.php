<?php

namespace Test\ICanBoogie\ActiveRecord\Validate;

use ICanBoogie\ActiveRecord;

class Sample extends ActiveRecord
{
    public string $email;

    public function create_validation_rules(): array
    {
        return [

            'email' => 'required|email'

        ];
    }
}
