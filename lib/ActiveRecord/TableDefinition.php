<?php

namespace ICanBoogie\ActiveRecord;

use function ICanBoogie\singularize;
use function strrpos;
use function substr;

/**
 * @internal
 *
 * A table definition, built during configuration.
 */
class TableDefinition
{
    /**
     * @param array{
     *     name: string,
     *     schema: Schema,
     *     alias: ?string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(... $an_array);
    }

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
