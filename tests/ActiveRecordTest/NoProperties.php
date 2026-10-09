<?php

namespace Test\ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Record whose persistent properties are always empty.
 */
final class NoProperties extends ActiveRecord
{
    public int $id;

    public string $name;

    /**
     * @inheritdoc
     */
    protected function alter_persistent_properties(array $properties, ActiveRecord\Schema $schema): array
    {
        return [];
    }
}
