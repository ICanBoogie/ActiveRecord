<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord;

use Closure;
use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Config\Association;
use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\Config\BelongsToAssociation;
use ICanBoogie\ActiveRecord\Config\HasManyAssociation;
use ICanBoogie\ActiveRecord\Config\TransientAssociation;
use ICanBoogie\ActiveRecord\Config\TransientBelongsToAssociation;
use ICanBoogie\ActiveRecord\Config\TransientHasManyAssociation;
use InvalidArgumentException;
use LogicException;

use function array_filter;
use function preg_match;

final class ConfigBuilder
{
    private const REGEXP_TIMEZONE = '/^[-+]\d{2}:\d{2}$/';

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    private static function filter_non_null(array $array): array
    {
        return array_filter($array, fn(mixed $v): bool => $v !== null);
    }

    /**
     * @var array<string, array<ConnectionOptions::*, mixed>>
     */
    private array $connections = [];

    /**
     * @var array<string, array<Model::*, mixed>>
     */
    private array $models = [];

    /**
     * @var array<string, TransientAssociation>
     *     Where _key_ is a model identifier.
     */
    private array $association = [];

    public function build(): Config
    {
        $associations = $this->build_associations();

        foreach ($associations as $model_id => $association) {
            $this->models[$model_id][Model::BELONGS_TO] = array_map(
                fn(BelongsToAssociation $a) => [
                    $a->model_id,
                    [
                        'local_key' => $a->local_key,
                        'foreign_key' => $a->foreign_key,
                        'as' => $a->as,
                    ]
                ],
                $association->belongs_to
            );

            $this->models[$model_id][Model::HAS_MANY] = array_map(
                fn(HasManyAssociation $a) => [
                    $a->model_id,
                    [
                        'local_key' => $a->local_key,
                        'foreign_key' => $a->foreign_key,
                        'as' => $a->as,
                        'through' => $a->through,
                    ]
                ],
                $association->has_many
            );
        }

        return new Config($this->connections, $this->models);
    }

    /**
     * @return array<string, Association>
     */
    private function build_associations(): array
    {
        $associations = [];

        foreach ($this->association as $model_id => $association) {
            $belongs_to = array_map(
                fn(TransientBelongsToAssociation $a): BelongsToAssociation => $this->resolve_belongs_to($model_id, $a),
                $association->belongs_to
            );

            $has_many = array_map(
                fn(TransientHasManyAssociation $a): HasManyAssociation => $this->resolve_has_many($model_id, $a),
                $association->has_many
            );

            $associations[$model_id] = new Association(
                belongs_to: $belongs_to,
                has_many: $has_many,
            );
        }

        return $associations;
    }

    private function resolve_belongs_to(string $owner, TransientBelongsToAssociation $association): BelongsToAssociation
    {
        $local_key = $association->local_key ?? throw new LogicException("Don't know how to resolve local key on $owner for association belongs_to($association->model_id)");
        $foreign_key = $association->foreign_key ?? $this->models[$association->model_id][Model::SCHEMA]->primary;
        $as = $association->as ?? $association->model_id;

        return new BelongsToAssociation(
            $association->model_id,
            $local_key,
            $foreign_key,
            $as,
        );
    }

    private function resolve_has_many(string $owner, TransientHasManyAssociation $association): HasManyAssociation
    {
        $local_key = $association->local_key ?? $this->models[$owner][Model::SCHEMA]->primary;
        $foreign_key = $association->foreign_key ?? $this->models[$association->model_id][Model::SCHEMA]->primary; // TODO: improve to infer key from 'belong_to'
        $as = $association->as ?? $association->model_id;

        return new HasManyAssociation(
            $association->model_id,
            $local_key,
            $foreign_key,
            $as,
            $association->through,
        );
    }

    /**
     * @return $this
     */
    public function add_connection(
        string $id,
        string $dsn,
        string|null $username = null,
        string|null $password = null,
        string|null $table_name_prefix = null,
        string $charset_and_collate = ConnectionOptions::DEFAULT_CHARSET_AND_COLLATE,
        string $time_zone = ConnectionOptions::DEFAULT_TIMEZONE,
    ): self {
        $this->assert_time_zone($time_zone);

        $this->connections[$id] = self::filter_non_null([ // @phpstan-ignore-line
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
            'options' => self::filter_non_null([
                ConnectionOptions::ID => $id,
                ConnectionOptions::TABLE_NAME_PREFIX => $table_name_prefix,
                ConnectionOptions::CHARSET_AND_COLLATE => $charset_and_collate,
                ConnectionOptions::TIMEZONE => $time_zone,
            ])
        ]);

        return $this;
    }

    private function assert_time_zone(string $time_zone): void
    {
        $pattern = self::REGEXP_TIMEZONE;

        if (!preg_match($pattern, $time_zone)) {
            throw new InvalidArgumentException("Time zone doesn't match pattern '$pattern': $time_zone");
        }
    }

    /**
     * @param (Closure(SchemaBuilder $schema): SchemaBuilder) $schema_builder
     */
    public function add_model(
        string $id,
        Closure $schema_builder,
        string $activerecord_class,
        string $connection = 'primary',
        string|null $name = null,
        string|null $alias = null,
        string|null $extends = null,
        string|null $implements = null,
        string|null $model_class = null,
        string|null $query_class = null,
        Closure $association_builder = null,
    ): self {
        if ($activerecord_class === ActiveRecord::class) {
            throw new LogicException("\$activerecord_class must be an extension of ICanBoogie\ActiveRecord");
        }

        $inner_schema_builder = new SchemaBuilder();
        $schema_builder($inner_schema_builder);
        $schema = $inner_schema_builder->build();

        if ($association_builder) {
            $inner_association_builder = new AssociationBuilder();
            $association_builder($inner_association_builder);
            $this->association[$id] = $inner_association_builder->build();
        }

        $this->models[$id] = self::filter_non_null([ // @phpstan-ignore-line
            Table::SCHEMA => $schema,
            Table::CONNECTION => $connection,
            Table::NAME => $name,
            Table::ALIAS => $alias,
            Table::EXTENDING => $extends,
            Table::IMPLEMENTING => $implements,
            Model::ID => $id,
            Model::ACTIVERECORD_CLASS => $activerecord_class,
            Model::CLASSNAME => $model_class,
            Model::QUERY_CLASS => $query_class,
        ]);

        return $this;
    }
}
