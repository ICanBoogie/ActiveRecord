<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Exception;
use LogicException;
use Throwable;

class InvalidConfig extends LogicException implements Exception
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous:  $previous);
    }
}
