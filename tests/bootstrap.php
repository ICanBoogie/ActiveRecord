<?php

namespace Test\ICanBoogie;

use ICanBoogie\ActiveRecord;
use ICanBoogie\Prototype;

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Madrid');

Prototype::bind(
    new Prototype\ConfigBuilder()
        ->bind(ActiveRecord::class, 'validate', function (ActiveRecord $record) {
            static $validate;

            $validate ??= new ActiveRecord\Validate\ValidateActiveRecord();

            return $validate($record);
        })
        ->build()
);
