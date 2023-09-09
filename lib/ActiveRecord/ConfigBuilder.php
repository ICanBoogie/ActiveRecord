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
use ICanBoogie\ActiveRecord\Config\Assert;
use ICanBoogie\ActiveRecord\Config\Association;
use ICanBoogie\ActiveRecord\Config\AssociationBuilder;
use ICanBoogie\ActiveRecord\Config\BelongsToAssociation;
use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\Config\HasManyAssociation;
use ICanBoogie\ActiveRecord\Config\InvalidConfig;
use ICanBoogie\ActiveRecord\Config\ModelDefinition;
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
use function get_parent_class;
use function ICanBoogie\pluralize;
use function ICanBoogie\singularize;
use function ICanBoogie\trim_suffix;
use function ICanBoogie\underscore;
use function is_a;
use function is_string;
use function preg_match;
use function sprintf;
use function strrpos;
use function substr;

final class ConfigBuilder
{
    private const REGEXP_TIMEZONE = '/^[-+]\d{2}:\d{2}$/';

    /**
     * @var array<string, ConnectionDefinition>
     */
    private array $connections = [];

    /**
     * @var array<class-string<Model>, TransientModelDefinition>
     */
    private array $transient_model_definitions_by_class = [];

    /**
     * @var array<class-string<Model>, TransientAssociation>
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
        foreach ($this->transient_model_definitions_by_class as $definition) {
            if (empty($this->connections[$definition->connection])) {
                throw new InvalidConfig("Model '$definition->model_class' uses connection '$definition->connection', but it is not configured.");
            }

            $this->resolve_parent_definition($definition);
        }
    }

    /**
     * @return array<class-string<Model>, Association>
     */
    private function build_associations(): array
    {
        foreach ($this->transient_model_definitions_by_class as $definition) {
            $parent = $this->resolve_parent_definition($definition);

            if (!$parent) {
                continue;
            }

            $parent_schema = $parent->schema;
            $primary = $parent_schema->primary;

            if (!is_string($primary)) {
                throw new InvalidConfig(
                    "Model '$definition->model_class' cannot extend '$parent->model_class',"
                    . " the primary key is not a string, given: " . get_debug_type($primary)
                );
            }

            $schema = $definition->schema;
            $parent_column = $parent_schema->columns[$primary];

            assert($parent_column instanceof Integer);

            $definition->schema = new Schema(
                columns: [ $primary => new Integer(size: $parent_column->size, unique: true) ] + $schema->columns,
                primary: $primary,
                indexes: $schema->indexes,
            );
        }

        $associations = [];

        foreach ($this->association as $model_class => $association) {
            $owner = $this->transient_model_definitions_by_class[$model_class];

            $belongs_to = array_map(
                fn(TransientBelongsToAssociation $a): BelongsToAssociation => $this->resolve_belongs_to($owner, $a),
                $association->belongs_to
            );

            $has_many = array_map(
                fn(TransientHasManyAssociation $a): HasManyAssociation => $this->resolve_has_many($owner, $a),
                $association->has_many
            );

            $associations[$model_class] = new Association(
                belongs_to: $belongs_to,
                has_many: $has_many,
            );
        }

        return $associations;
    }

    private function resolve_parent_definition(TransientModelDefinition $definition): ?TransientModelDefinition
    {
        $parent_class = get_parent_class($definition->model_class);

        if ($parent_class === Model::class) {
            return null;
        }

        return $this->transient_model_definitions_by_class[$parent_class]
            ?? throw new LogicException("The model '$definition->model_class' extends '$parent_class' but there's no definition for it");
    }

    /**
     * Builds model configuration from model transient configurations and association configurations.
     *
     * @param array<class-string<Model>, Association> $associations
     *
     * @return array<class-string<Model>, ModelDefinition>
     */
    private function build_models(array $associations): array
    {
        $models = [];

        foreach ($this->transient_model_definitions_by_class as $class => $transient) {
            $models[$class] = new ModelDefinition(
                table: new TableDefinition(
                    name: $transient->table_name,
                    schema: $transient->schema,
                    alias: $transient->alias,
                ),
                model_class: $transient->model_class,
                activerecord_class: $transient->activerecord_class,
                connection: $transient->connection,
                association: $associations[$class] ?? null,
            );
        }

        return $models;
    }

    private function try_key(mixed $key, TransientModelDefinition $on): ?string
    {
        if (!is_string($key)) {
            return null;
        }

        return $on->schema->has_column($key) ? $key : null;
    }

    private function resolve_belongs_to(
        TransientModelDefinition $owner,
        TransientBelongsToAssociation $association
    ): BelongsToAssociation {
        $associate = $this->model_definition_for_activerecord($association->associate);
        $foreign_key = $associate->schema->primary;

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'belongs to' association, primary key of model '$associate->model_class' is not a string."
            );
        }

        $local_key = $association->local_key
            ?? $this->try_key($foreign_key, $owner)
            ?? throw new LogicException(
                "Don't know how to resolve local key on '$owner->model_class' for association belongs_to($associate->model_class)"
            );

        $as = $association->as
            ?? singularize($associate->alias);

        return new BelongsToAssociation(
            $associate->model_class,
            $local_key,
            $foreign_key,
            $as,
        );
    }

    private function resolve_has_many(
        TransientModelDefinition $owner,
        TransientHasManyAssociation $association
    ): HasManyAssociation {
        $related = $this->model_definition_for_activerecord($association->associate);
        $local_key = $association->local_key ?? $owner->schema->primary;
        $foreign_key = $association->foreign_key;
        $as = $association->as ?? pluralize($related->alias);

        if ($association->through) {
            $foreign_key ??= $related->schema->primary;
        } else {
            $foreign_key ??= $this->try_key($owner->schema->primary, $related);
        }

        $foreign_key or throw new InvalidConfig(
            "Don't know how to resolve foreign key on '$owner->model_class' for association has_many($related->model_class)"
        );

        if (!is_string($local_key)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$owner->model_class' is not a string."
            );
        }

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$related->model_class' is not a string."
            );
        }

        $through = null;

        if ($association->through) {
            $through = $this->model_definition_for_activerecord($association->through);
        }

        return new HasManyAssociation(
            $related->model_class,
            $local_key,
            $foreign_key,
            $as,
            $through?->model_class,
        );
    }

    private function model_definition_for_activerecord(string $activerecord_class): TransientModelDefinition
    {
        $model_class = $this->model_class_by_active_record_class[$activerecord_class]
            ?? throw new LogicException("No model defined for ActiveRecord class '$activerecord_class'");

        return $this->transient_model_definitions_by_class[$model_class]
            ?? throw new LogicException("No model defined for Model class '$model_class'");
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
     * @var array<class-string<ActiveRecord>, class-string<Model>>
     */
    private array $model_class_by_active_record_class = []; // @phpstan-ignore-line

    /**
     * @param class-string<Model> $model_class
     * @param (Closure(SchemaBuilder $schema): SchemaBuilder)|null $schema_builder
     */
    public function add_model( // @phpstan-ignore-line
        string $model_class,
        string|null $table_name = null,
        string|null $alias = null,
        Closure $schema_builder = null,
        Closure $association_builder = null,
        string $connection = Config::DEFAULT_CONNECTION_ID,
    ): self {
        Assert::extends_model($model_class);

        $activerecord_class = ActiveRecord\Model\Record::resolve_activerecord_class($model_class);

        $this->model_class_by_active_record_class[$activerecord_class] = $model_class;

        //

        [ $inner_schema_builder, $inner_association_builder ] = $this->create_builders($activerecord_class);

        // schema

        if ($schema_builder) {
            $schema_builder($inner_schema_builder);
        } elseif ($this->use_attributes && $inner_schema_builder->is_empty()) {
            throw new LogicException("the config is built using attributes but there's no schema for '$activerecord_class'");
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

        $this->association[$model_class] = $inner_association_builder->build();

        // transient model

        $table_name ??= self::resolve_table_name($activerecord_class);

        $this->transient_model_definitions_by_class[$model_class] = new TransientModelDefinition(
            schema: $schema,
            model_class: $model_class,
            activerecord_class: $activerecord_class,
            table_name: $table_name,
            alias: $alias ?? singularize($table_name),
            connection: $connection,
        );

        return $this;
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     */
    private static function resolve_table_name(string $activerecord_class): string
    {
        $pos = strrpos($activerecord_class, '\\');
        $base = substr($activerecord_class, $pos + 1);

        return pluralize(underscore($base));
    }

    /**
     * @param non-empty-string $local_key
     *
     * @return non-empty-string
     */
    private function resolve_belong_to_accessor(string $local_key): string
    {
        $local_key = trim_suffix($local_key, '_id');

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
     * @param class-string<ActiveRecord> $activerecord_class
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
     * @param class-string<ActiveRecord> $activerecord_class
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
