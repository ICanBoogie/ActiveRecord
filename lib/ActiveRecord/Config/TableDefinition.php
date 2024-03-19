<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Schema;

use function ICanBoogie\singularize;
use function strrpos;
use function substr;

/**
 * @internal
 *
 * A table definition, built during configuration.
 */
readonly class TableDefinition
{
    /**
     * @param array{
     *     name: non-empty-string,
     *     schema: Schema,
     *     alias: ?non-empty-string,
     * } $an_array
     */
    public static function __set_state(array $an_array): self
    {
        return new self(... $an_array);
    }

    /**
     * @var non-empty-string
     */
    public string $alias;

    /**
     * @param non-empty-string $name
     *     Unprefixed name of the table.
     * @param non-empty-string|null $alias
     *     The alias for that table.
     * @param Schema $schema
     *     Schema of the table.
     */
    public function __construct(
        public string $name,
        public Schema $schema,
        ?string $alias = null,
    ) {
        $this->alias = $alias ?? $this->make_alias($this->name);
    }

    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    private function make_alias(string $name): string
    {
        $pos = strrpos($name, '_');
        $alias = $pos !== false
            ? substr($name, $pos + 1)
            : $name;

        return singularize($alias);
    }
}
