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
use ICanBoogie\ActiveRecord\Config\TableDefinition;
use ICanBoogie\ActiveRecord\Config\TransientAssociation;
use ICanBoogie\ActiveRecord\Config\TransientHasManyAssociation;
use ICanBoogie\ActiveRecord\Config\TransientModelDefinition;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Integer;
use InvalidArgumentException;
use LogicException;

use function array_map;
use function assert;
use function get_debug_type;
use function get_parent_class;
use function ICanBoogie\pluralize;
use function ICanBoogie\singularize;
use function ICanBoogie\underscore;
use function is_string;
use function preg_match;
use function strlen;
use function strrpos;
use function substr;

final class ConfigBuilder
{
    private const REGEXP_TIMEZONE = '/^[-+]\d{2}:\d{2}$/';

    /**
     * @var array<non-empty-string, ConnectionDefinition>
     */
    private array $connections = [];

    /**
     * @var array<class-string<ActiveRecord>, TransientModelDefinition>
     */
    private array $model_definitions = [];

    /**
     * @var array<class-string<ActiveRecord>, TransientAssociation>
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
        foreach ($this->model_definitions as $definition) {
            $this->connections[$definition->connection] ?? throw new InvalidConfig(
                "$definition->activerecord_class uses connection '$definition->connection', but it is not configured"
            );

            $this->resolve_parent_definition($definition);
        }
    }

    /**
     * @return array<class-string<ActiveRecord>, Association>
     */
    private function build_associations(): array
    {
        foreach ($this->model_definitions as $definition) {
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

        foreach ($this->association as $activerecord_class => $association) {
            $owner = $this->model_definitions[$activerecord_class];

            $belongs_to = [];

            foreach ($owner->schema->belongs_to_iterator() as $name => $column) {
                /** @var BelongsTo $column */
                $belongs_to[] = $this->resolve_belongs_to($name, $column);
            }

            $has_many = array_map(
                fn(TransientHasManyAssociation $a): HasManyAssociation => $this->resolve_has_many($owner, $a),
                $association->has_many
            );

            $associations[$activerecord_class] = new Association(
                belongs_to: $belongs_to,
                has_many: $has_many,
            );
        }

        return $associations;
    }

    private function resolve_parent_definition(TransientModelDefinition $definition): ?TransientModelDefinition
    {
        $parent_class = get_parent_class($definition->activerecord_class);

        if ($parent_class === ActiveRecord::class) {
            return null;
        }

        return $this->model_definitions[$parent_class]
            ?? throw new LogicException(
                "The '$definition->activerecord_class' extends '$parent_class' but there's no definition for it"
            );
    }

    /**
     * Builds model configuration from model transient configurations and association configurations.
     *
     * @param array<class-string<ActiveRecord>, Association> $associations
     *
     * @return array<class-string<ActiveRecord>, ModelDefinition>
     */
    private function build_models(array $associations): array
    {
        $models = [];

        foreach ($this->model_definitions as $activerecord_class => $transient) {
            $models[$activerecord_class] = new ModelDefinition(
                table: new TableDefinition(
                    name: $transient->table_name,
                    schema: $transient->schema,
                    alias: $transient->alias,
                ),
                model_class: $transient->model_class,
                activerecord_class: $transient->activerecord_class,
                query_class: $transient->query_class,
                connection: $transient->connection,
                association: $associations[$activerecord_class] ?? null,
            );
        }

        return $models;
    }

    /**
     * @return non-empty-string|null
     */
    private function try_key(mixed $key, TransientModelDefinition $on): ?string
    {
        if (!is_string($key)) {
            return null;
        }

        assert(strlen($key) > 1);

        return $on->schema->has_column($key) ? $key : null;
    }

    /**
     * @param non-empty-string $local_key
     */
    private function resolve_belongs_to(string $local_key, Schema\BelongsTo $column): BelongsToAssociation
    {
        $associate = $this->model_definitions[$column->associate];
        $foreign_key = $associate->schema->primary;

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'belongs to' association, primary key of `$associate->activerecord_class` is not a string."
            );
        }

        $as = $column->as ?? singularize($associate->alias);

        assert(strlen($as) > 0);

        return new BelongsToAssociation(
            $associate->activerecord_class,
            $local_key,
            $foreign_key,
            $as,
        );
    }

    private function resolve_has_many(
        TransientModelDefinition $owner,
        TransientHasManyAssociation $association
    ): HasManyAssociation {
        if (!is_string($owner->schema->primary)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$owner->activerecord_class' is not a string."
            );
        }

        $related = $this->model_definitions[$association->associate];
        $foreign_key = $association->foreign_key;
        $as = $association->as ?? pluralize($related->alias);

        if ($association->through) {
            $foreign_key ??= $related->schema->primary;
        } else {
            $foreign_key ??= $this->try_key($owner->schema->primary, $related);
        }

        $foreign_key or throw new InvalidConfig(
            "Don't know how to resolve foreign key on `$owner->activerecord_class` for association has_many($related->model_class)"
        );

        if (!is_string($foreign_key)) {
            throw new InvalidConfig(
                "Unable to create 'has many' association, primary key of model '$related->activerecord_class' is not a string."
            );
        }

        $through = null;

        if ($association->through) {
            $through = $this->model_definitions[$association->through];
        }

        assert(strlen($as) > 1);

        return new HasManyAssociation(
            associate: $related->activerecord_class,
            foreign_key: $foreign_key,
            as: $as,
            through: $through?->activerecord_class,
        );
    }

    /**
     * @param non-empty-string $id
     * @param non-empty-string $dsn
     * @param non-empty-string|null $username
     * @param non-empty-string|null $password
     * @param non-empty-string|null $table_name_prefix
     * @param non-empty-string $charset_and_collate
     * @param non-empty-string $time_zone
     *
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
     * @param class-string<ActiveRecord> $activerecord_class
     * @param class-string<Model> $model_class
     * @param class-string<Query> $query_class
     * @param non-empty-string|null $table_name
     * @param non-empty-string|null $alias
     * @param (Closure(SchemaBuilder): SchemaBuilder)|null $schema_builder
     * @param (Closure(AssociationBuilder): AssociationBuilder)|null $association_builder
     * @param non-empty-string $connection
     */
    public function add_model( // @phpstan-ignore-line
        string $activerecord_class,
        string $model_class = Model::class,
        string $query_class = Query::class,
        ?string $table_name = null,
        ?string $alias = null,
        Closure $schema_builder = null,
        Closure $association_builder = null,
        string $connection = Config::DEFAULT_CONNECTION_ID,
    ): self {
        Assert::extends_activerecord($activerecord_class);

        [ $inner_schema_builder, $inner_association_builder ] = $this->create_builders($activerecord_class);

        // schema

        if ($schema_builder) {
            $schema_builder($inner_schema_builder);
        } elseif ($this->use_attributes && $inner_schema_builder->is_empty()) {
            throw new LogicException("The Schema built from `$activerecord_class` attributes is empty");
        }

        // association

        $schema = $inner_schema_builder->build();

        if ($association_builder) {
            $association_builder($inner_association_builder);
        }

        $this->association[$activerecord_class] = $inner_association_builder->build();

        // transient model

        $table_name ??= self::resolve_table_name($activerecord_class);

        $this->model_definitions[$activerecord_class] = new TransientModelDefinition(
            schema: $schema,
            model_class: $model_class,
            activerecord_class: $activerecord_class,
            query_class: $query_class,
            table_name: $table_name,
            alias: $alias ?? singularize($table_name), // @phpstan-ignore-line
            connection: $connection,
        );

        return $this;
    }

    /**
     * @param class-string<ActiveRecord> $activerecord_class
     *
     * @return non-empty-string
     */
    private static function resolve_table_name(string $activerecord_class): string
    {
        $pos = strrpos($activerecord_class, '\\');
        $base = substr($activerecord_class, $pos + 1);

        return pluralize(underscore($base)); // @phpstan-ignore-line
    }

    private bool $use_attributes = false;

    /**
     * Enables the use of attributes to create schemas and associations.
     */
    public function use_attributes(): self
    {
        $this->use_attributes = true;

        return $this;
    }

    /**
     * Creates a schema builder and an association builder.
     *
     * If attributes are enabled they are configured using the attributes on the ActiveRecord.
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
            $schema_builder->use_record($activerecord_class);
            $association_builder->use_record($activerecord_class);
        }

        return [ $schema_builder, $association_builder ];
    }
}
