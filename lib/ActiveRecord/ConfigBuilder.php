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
use ICanBoogie\ActiveRecord\Config\ConnectionAttributes;
use ICanBoogie\ActiveRecord\Config\HasManyAssociation;
use ICanBoogie\ActiveRecord\Config\InvalidConfig;
use ICanBoogie\ActiveRecord\Config\TransientAssociation;
use ICanBoogie\ActiveRecord\Config\TransientBelongsToAssociation;
use ICanBoogie\ActiveRecord\Config\TransientHasManyAssociation;
use ICanBoogie\ActiveRecord\Config\TransientModelConfig;
use InvalidArgumentException;
use LogicException;

use function array_map;
use function array_pop;
use function explode;
use function get_debug_type;
use function ICanBoogie\singularize;
use function is_string;
use function preg_match;

final class ConfigBuilder
{
    private const REGEXP_TIMEZONE = '/^[-+]\d{2}:\d{2}$/';

    /**
     * @var array<string, ConnectionAttributes>
     */
    private array $connections = [];

    /**
     * @var array<string, TransientModelConfig>
     */
    private array $transient_models = [];

    /**
     * @var array<string, TransientAssociation>
     *     Where _key_ is a model identifier.
     */
    private array $association = [];

    public function build(): Config
    {
        $this->validate_models();

        $associations = $this->build_associations();
        $models = $this->build_models($associations);

        return new Config($this->connections, $models);
    }

    private function validate_models(): void
    {
        foreach ($this->transient_models as $id => $config) {
            if (empty($this->connections[$config->connection])) {
                throw new InvalidConfig("Model '$id' uses connection '$config->connection', but it is not configured.");
            }

            if ($config->extends && empty($this->transient_models[$config->extends])) {
                throw new InvalidConfig("Model '$id' extends '$config->extends', but it is not configured.");
            }

            if ($config->implements && empty($this->transient_models[$config->implements])) {
                throw new InvalidConfig("Model '$id' implements '$config->implements', but it is not configured.");
            }
        }
    }

    /**
     * @return array<string, Association>
     */
    private function build_associations(): array
    {
        foreach ($this->transient_models as $id => $model) {
            if ($model->extends) {
                $parent_schema = $this->transient_models[$model->extends]->schema;
                $primary = $parent_schema->primary;

                if (!is_string($primary)) {
                    throw new InvalidConfig(
                        "Model '$id' cannot extend '$model->extends',"
                        . " the primary key is not a string, given: " . get_debug_type($primary)
                    );
                }

                $model->schema[$primary] = $parent_schema[$primary]->with([ 'auto_increment' => false ]);
            }
        }

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

    /**
     * Builds model configuration from model transient configurations and association configurations.
     *
     * @param array<string, Association> $associations
     *     Where _key_ is a model identifier.
     *
     * @return array<string, ModelAttributes>
     *     Where _key_ is a model identifier.
     */
    private function build_models(array $associations): array
    {
        $models = [];

        foreach ($this->transient_models as $id => $transient) {
            $models[$id] = new ModelAttributes(
                id: $id,
                connection: $transient->connection,
                schema: $transient->schema,
                activerecord_class: $transient->activerecord_class,
                name: $transient->name,
                alias: $transient->alias,
                extends: $transient->extends,
                implements: $transient->implements,
                model_class: $transient->model_class ?? Model::class,
                query_class: $transient->query_class ?? Query::class,
                association: $associations[$id] ?? null,
            );
        }

        return $models;
    }

    private function resolve_belongs_to(string $owner, TransientBelongsToAssociation $association): BelongsToAssociation
    {
        $related = $association->model_id;
        $local_key = $association->local_key ?? throw new LogicException(
            "Don't know how to resolve local key on $owner for association belongs_to($related)"
        );
        $foreign_key = $association->foreign_key ?? $this->transient_models[$related]->schema->primary;
        $as = $association->as ?? singularize($related);

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'belongs to' association, primary key of model '$related' is not a string."
            );
        }

        return new BelongsToAssociation(
            $related,
            $local_key,
            $foreign_key,
            $as,
        );
    }

    private function resolve_has_many(string $owner, TransientHasManyAssociation $association): HasManyAssociation
    {
        $local_key = $association->local_key
            ?? $this->transient_models[$owner]->schema->primary;
        $foreign_key = $association->foreign_key;
        $as = $association->as ?? $association->model_id;

        if ($association->through) {
            $foreign_key ??= $this->transient_models[$association->model_id]->schema->primary;
        }

        $foreign_key or throw new InvalidConfig(
            "Don't know how to resolve foreign key on $owner for association has_many($association->model_id)"
        );

        if (!is_string($local_key)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$owner' is not a string."
            );
        }

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$association->model_id' is not a string."
            );
        }

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
        string $charset_and_collate = ConnectionAttributes::DEFAULT_CHARSET_AND_COLLATE,
        string $time_zone = ConnectionAttributes::DEFAULT_TIMEZONE,
    ): self {
        $this->assert_time_zone($time_zone);

        $this->connections[$id] = new ConnectionAttributes(
            id: $id,
            dsn: $dsn,
            username: $username,
            password: $password,
            table_name_prefix: $table_name_prefix,
            charset_and_collate: $charset_and_collate,
            time_zone: $time_zone
        );

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
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<Model<int|string, ActiveRecord>>|null $model_class
     * @param class-string<Query<ActiveRecord>>|null $query_class
     */
    public function add_model(
        string $id,
        Closure $schema_builder,
        string $activerecord_class,
        string $connection = Config::DEFAULT_CONNECTION_ID,
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

        $this->transient_models[$id] = new TransientModelConfig(
            id: $id,
            schema: $schema,
            activerecord_class: $activerecord_class,
            connection: $connection,
            name: $name,
            alias: $alias,
            extends: $extends,
            implements: $implements,
            model_class: $model_class,
            query_class: $query_class,
        );

        return $this;
    }
}
