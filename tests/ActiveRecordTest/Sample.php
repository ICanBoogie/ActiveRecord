<?php

namespace Test\ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

use function is_int;

/**
 * Sample active record test case.
 *
 * @property-read int|null $id
 */
final class Sample extends ActiveRecord
{
    private int $id;

    protected function get_id(): ?int
    {
        return $this->id ?? null;
    }

    public string $reverse;

    /**
     * Reverses the value of the `reverse` property.
     *
     * @inheritdoc
     */
    protected function alter_persistent_properties(array $properties, ActiveRecord\Schema $schema): array
    {
        return array_merge(parent::alter_persistent_properties($properties, $schema), [

            'reverse' => strrev($this->reverse)

        ]);
    }

    /**
     * @param int|string|string[] $primary_key
     */
    protected function update_primary_key(int|array|string $primary_key): void
    {
        assert(is_int($primary_key));

        $this->id = $primary_key;
    }
}
