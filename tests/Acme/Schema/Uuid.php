<?php

namespace Test\ICanBoogie\Acme\Schema;

use Attribute;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\ResolvesToColumn;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Uuid extends Character implements ResolvesToColumn
{
    public function __construct(
        bool $null = false,
        bool $unique = false,
    ) {
        parent::__construct(
            size: 40,
            fixed: true,
            null: $null,
            unique: $unique,
        );
    }

    public function resolve(): Column
    {
        return new Character(
            size: $this->size,
            fixed: $this->fixed,
            null: $this->null,
            unique: $this->unique,
        );
    }
}
