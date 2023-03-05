<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Schema;

use ICanBoogie\ActiveRecord\Table;

use function ICanBoogie\singularize;
use function strrpos;
use function substr;

class TableConfig
{
    public readonly string $alias;

    /**
     * @param string $name
     *     Unprefixed name of the table.
     * @param string $connection
     *     Identifier of a database connection.
     * @param Schema $schema
     *     Schema of the table.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $connection,
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

    /**
     * @return array<Table::*, mixed>
     */
    public function to_array(): array
    {
        return [

            Table::NAME => $this->name,
            Table::CONNECTION => $this->connection,
            Table::SCHEMA => $this->schema,
            Table::ALIAS => $this->alias,
            Table::EXTENDING => $this->extends,
            Table::IMPLEMENTING => $this->implements,

        ];
    }
}
