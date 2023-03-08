<?php

namespace ICanBoogie\ActiveRecord;

use function ICanBoogie\singularize;
use function strrpos;
use function substr;

class TableAttributes
{
    public readonly string $alias;

    /**
     * @param string $name
     *     Unprefixed name of the table.
     * @param Schema $schema
     *     Schema of the table.
     */
    public function __construct(
        public readonly string $name,
        public readonly Schema $schema,
        ?string $alias = null,
        public readonly ?string $extends = null,
        public readonly ?string $implements = null,
    ) {
        $this->alias = $alias ?? $this->make_alias($this->name);
    }

    private function make_alias(string $name): string
    {
        $pos = strrpos($name, '_');
        $alias = $pos !== false
            ? substr($name, $pos + 1)
            : $name;

        return singularize($alias);
    }
}
