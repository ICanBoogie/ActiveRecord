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
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Config\HasManyAssociation;
use ICanBoogie\ActiveRecord\Config\InvalidConfig;
use ICanBoogie\ActiveRecord\Config\TransientAssociation;
use ICanBoogie\ActiveRecord\Config\TransientBelongsToAssociation;
use ICanBoogie\ActiveRecord\Config\TransientHasManyAssociation;
use ICanBoogie\ActiveRecord\Config\TransientModelDefinition;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\SchemaAttribute;
use InvalidArgumentException;
use LogicException;
use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\TargetClass;
use olvlvl\ComposerAttributeCollector\TargetProperty;

use function array_map;
use function assert;
use function class_exists;
use function get_debug_type;
use function ICanBoogie\singularize;
use function is_a;
use function is_string;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function substr;

final class ConfigBuilder
{
    private const REGEXP_TIMEZONE = '/^[-+]\d{2}:\d{2}$/';

    /**
     * @var array<string, ConnectionDefinition>
     */
    private array $connections = [];

    /**
     * @var array<string, TransientModelDefinition>
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
            if (!$model->extends) {
                continue;
            }

            $parent_schema = $this->transient_models[$model->extends]->schema;
            $primary = $parent_schema->primary;

            if (!is_string($primary)) {
                throw new InvalidConfig(
                    "Model '$id' cannot extend '$model->extends',"
                    . " the primary key is not a string, given: " . get_debug_type($primary)
                );
            }

            $schema = $model->schema;
            $parent_column = $parent_schema->columns[$primary];

            assert($parent_column instanceof Integer);

            $model->schema = new Schema(
                columns: [ $primary => new Integer(size: $parent_column->size, unique: true) ] + $schema->columns,
                primary: $primary,
                indexes: $schema->indexes,
            );
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
     * @return array<string, ModelDefinition>
     *     Where _key_ is a model identifier.
     */
    private function build_models(array $associations): array
    {
        $models = [];

        foreach ($this->transient_models as $id => $transient) {
            $models[$id] = new ModelDefinition(
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

    private function try_key(mixed $key, string $on): ?string
    {
        if (!is_string($key)) {
            return null;
        }

        $schema = $this->transient_models[$on]->schema;

        return $schema->has_column($key) ? $key : null;
    }

    private function resolve_belongs_to(string $owner, TransientBelongsToAssociation $association): BelongsToAssociation
    {
        $associate = $this->resolve_model_id($association->associate);
        $foreign_key = $this->transient_models[$associate]->schema->primary;

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'belongs to' association, primary key of model '$associate' is not a string."
            );
        }

        $local_key = $association->local_key
            ?? $this->try_key($foreign_key, $owner)
            ?? throw new LogicException(
                "Don't know how to resolve local key on '$owner' for association belongs_to($associate)"
            );

        $as = $association->as
            ?? singularize($associate);

        return new BelongsToAssociation(
            $associate,
            $local_key,
            $foreign_key,
            $as,
        );
    }

    private function resolve_has_many(string $owner, TransientHasManyAssociation $association): HasManyAssociation
    {
        $related = $this->resolve_model_id($association->associate);
        $local_key = $association->local_key ?? $this->transient_models[$owner]->schema->primary;
        $foreign_key = $association->foreign_key;
        $as = $association->as ?? $related;

        if ($association->through) {
            $foreign_key ??= $this->transient_models[$related]->schema->primary;
        } else {
            $foreign_key ??= $this->try_key($this->transient_models[$owner]->schema->primary, $related);
        }

        $foreign_key or throw new InvalidConfig(
            "Don't know how to resolve foreign key on '$owner' for association has_many($related)"
        );

        if (!is_string($local_key)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$owner' is not a string."
            );
        }

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$related' is not a string."
            );
        }

        $through = $association->through;

        if ($through) {
            $through = $this->resolve_model_id($through);
        }

        return new HasManyAssociation(
            $related,
            $local_key,
            $foreign_key,
            $as,
            $through,
        );
    }

    private function resolve_model_id(string $model_id_or_active_record_class): string
    {
        return $this->model_aliases[$model_id_or_active_record_class] ?? $model_id_or_active_record_class;
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
        string $charset_and_collate = ConnectionDefinition::DEFAULT_CHARSET_AND_COLLATE,
        string $time_zone = ConnectionDefinition::DEFAULT_TIMEZONE,
    ): self {
        $this->assert_time_zone($time_zone);

        $this->connections[$id] = new ConnectionDefinition(
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
     * @var array<class-string, string>
     *     Where _key_ is an ActiveRecord class and _value_ a model identifier.
     */
    private array $model_aliases = [];

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<Model>|null $model_class
     * @param class-string<Query>|null $query_class
     * @param (Closure(SchemaBuilder $schema): SchemaBuilder)|null $schema_builder
     */
    public function add_model( // @phpstan-ignore-line
        string $id,
        string $activerecord_class,
        string|null $model_class = null,
        string|null $query_class = null,
        string|null $name = null,
        string|null $alias = null,
        string|null $extends = null,
        string|null $implements = null,
        Closure $schema_builder = null,
        Closure $association_builder = null,
        string $connection = Config::DEFAULT_CONNECTION_ID,
    ): self {
        if ($activerecord_class === ActiveRecord::class) {
            throw new LogicException("\$activerecord_class must be an extension of ICanBoogie\ActiveRecord");
        }

        $this->model_aliases[$activerecord_class] = $id;

        //

        [ $inner_schema_builder, $inner_association_builder ] = $this->create_builders($activerecord_class);

        // schema

        if ($schema_builder) {
            $schema_builder($inner_schema_builder);
        } elseif ($this->use_attributes && $inner_schema_builder->is_empty()) {
            throw new LogicException("expected schema builder because the config was built from attributes but there's no schema for $activerecord_class");
        }

        $schema = $inner_schema_builder->build();

        // association

        foreach ($schema->columns as $local_key => $column) {
            if (!$column instanceof BelongsTo) {
                continue;
            }

            $inner_association_builder->belongs_to(
                associate: $column->associate,
                local_key: $local_key,
                as: $column->as ?? $this->resolve_belong_to_accessor($local_key),
            );
        }

        if ($association_builder) {
            $association_builder($inner_association_builder);
        }

        $this->association[$id] = $inner_association_builder->build();

        // transient model

        $this->transient_models[$id] = new TransientModelDefinition(
            id: $id,
            schema: $schema,
            activerecord_class: $activerecord_class,
            model_class: $model_class,
            query_class: $query_class,
            name: $name,
            alias: $alias,
            extends: $extends,
            implements: $implements,
            connection: $connection,
        );

        return $this;
    }

    /**
     * @param non-empty-string $local_key
     *
     * @return non-empty-string
     */
    private function resolve_belong_to_accessor(string $local_key): string
    {
        if (str_ends_with($local_key, '_id')) {
            $local_key = substr($local_key, 0, -3);
        }

        assert($local_key !== '');

        return $local_key;
    }

    private bool $use_attributes = false;

    /**
     * Enables the use of attributes to create schemas and associations.
     */
    public function use_attributes(): self
    {
        if (!class_exists(Attributes::class)) {
            throw new LogicException(
                sprintf(
                    "unable to load %s, is the package olvlvl/composer-attribute-collector activated?",
                    Attributes::class
                )
            );
        }

        $this->use_attributes = true;

        return $this;
    }

    /**
     * Creates a schema builder and an association builder, if attributes are enabled they are configured using them.
     *
     * @param class-string $activerecord_class
     *     An ActiveRecord class.
     *
     * @return array{ SchemaBuilder, AssociationBuilder }
     */
    private function create_builders(string $activerecord_class): array
    {
        $schema_builder = new SchemaBuilder();
        $association_builder = new AssociationBuilder();

        if ($this->use_attributes) {
            [ $class_targets, $target_properties ] = $this->find_attribute_targets($activerecord_class);

            $class_attributes = array_map(fn(TargetClass $t) => $t->attribute, $class_targets);
            $property_attributes = array_map(fn(TargetProperty $t) => [ $t->attribute, $t->name ], $target_properties);

            $schema_builder->from_attributes($class_attributes, $property_attributes);
            $association_builder->from_attributes($class_attributes);
        }

        return [ $schema_builder, $association_builder ];
    }

    /**
     * @param class-string $activerecord_class
     *     An ActiveRecord class.
     *
     * @return array{
     *     TargetClass<SchemaAttribute>[],
     *     TargetProperty<SchemaAttribute>[],
     * }
     */
    private function find_attribute_targets(string $activerecord_class): array
    {
        $predicate = fn(string $attribute, string $class): bool =>
            is_a($attribute, SchemaAttribute::class, true)
            && $class === $activerecord_class;

        /** @var TargetClass<SchemaAttribute>[] $target_classes */
        $target_classes = Attributes::filterTargetClasses($predicate);

        /** @var TargetProperty<SchemaAttribute>[] $target_properties */
        $target_properties = Attributes::filterTargetProperties($predicate);

        return [ $target_classes, $target_properties ];
    }
}
