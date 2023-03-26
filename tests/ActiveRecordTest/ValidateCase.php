<?php

namespace Test\ICanBoogie\ActiveRecordTest;

use ICanBoogie\ActiveRecord;

/**
 * Validate test case.
 *
 * @property-read string $timezone.
 */
class ValidateCase extends ActiveRecord
{
    private int $id;

    protected function get_id(): ?int
    {
        return $this->id ?? null;
    }

    public string $name;

    public string $email;

    private string $timezone = 'Europe\Pas';

    protected function get_timezone(): string
    {
        return $this->timezone;
    }

    /**
     * @inheritdoc
     */
    public function create_validation_rules(): array
    {
        return parent::create_validation_rules() + [

                'name' => 'required|min-length:3',
                'email' => 'required|email|unique',
                'timezone' => 'required|timezone'

            ];
    }
}
