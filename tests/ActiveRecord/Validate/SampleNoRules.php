<?php

namespace Test\ICanBoogie\ActiveRecord\Validate;

use ICanBoogie\ActiveRecord;

class SampleNoRules extends ActiveRecord
{
    public const MODEL_ID = 'model';

    public string $email;
}
