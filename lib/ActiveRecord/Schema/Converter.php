<?php

namespace ICanBoogie\ActiveRecord\Schema;

#[\Attribute]
readonly class Converter
{
    /**
     * @param class-string<Converter> $converter
     */
    public function __construct(
        public string $converter
    ) {
    }
}
