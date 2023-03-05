<?php

namespace ICanBoogie\ActiveRecord\Config;

use ICanBoogie\ActiveRecord\Model;

use function array_map;

/**
 * @internal
 */
final class Association
{
    /**
     * @param array<BelongsToAssociation> $belongs_to
     * @param array<HasManyAssociation> $has_many
     */
    public function __construct(
        public readonly array $belongs_to,
        public readonly array $has_many,
    ) {
    }

    /**
     * @return array{
     *     belongs_to: array<string, mixed>,
     *     has_many: array<string, mixed>,
     * }
     */
    public function to_array(): array
    {
        /** @phpstan-ignore-next-line */
        return [

            Model::BELONGS_TO => count($this->belongs_to) === 0 ? null : array_map(
                fn(BelongsToAssociation $a) => [
                    $a->model_id,
                    [
                        'local_key' => $a->local_key,
                        'foreign_key' => $a->foreign_key,
                        'as' => $a->as,
                    ]
                ],
                $this->belongs_to
            ),

            Model::HAS_MANY => count($this->has_many) === 0 ? null : array_map(
                fn(HasManyAssociation $a) => [
                    $a->model_id,
                    [
                        'local_key' => $a->local_key,
                        'foreign_key' => $a->foreign_key,
                        'as' => $a->as,
                        'through' => $a->through,
                    ]
                ],
                $this->has_many
            ),

        ];
    }
}
