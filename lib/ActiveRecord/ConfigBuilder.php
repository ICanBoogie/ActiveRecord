<?php

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
use ICanBoogie\ActiveRecord\Schema\Integer;
use InvalidArgumentException;
use LogicException;
use Throwable;

use function assert;
use function count;
use function get_parent_class;
use function ICanBoogie\trim_suffix;
use function implode;
use function is_a;
use function is_string;
use function json_encode;
use function preg_match;
use function strlen;
use function strrpos;
use function substr;

final class ConfigBuilder
{
    private const string REGEXP_TIMEZONE = '/^[-+]\d{2}:\d{2}$/';
    public const string ID_SUFFIX = '_id';

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
        $this->resolve_foreign_keys();
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
                    "$definition->activerecord_class cannot extend $parent->activerecord_class,"
                    . " the primary key is not a column, given: " . json_encode($primary)
                );
            }

            $schema = $definition->schema;
            $parent_column = $parent_schema->columns[$primary];

            assert($parent_column instanceof Integer);

            $definition->schema = new Schema(
                columns: [
                    // The child shares the primary key of its parent, its type must be the same.
                    $primary => new Integer(
                        size: $parent_column->size,
                        unsigned: $parent_column->unsigned,
                        unique: true,
                    ),
                ] + $schema->columns,
                primary: $primary,
                indexes: $schema->indexes,
            );
        }

        $associations = [];

        foreach ($this->association as $activerecord_class => $association) {
            $owner = $this->model_definitions[$activerecord_class];

            $belongs_to = [];

            foreach ($owner->schema->belongs_to_iterator() as $name => $column) {
                try {
                    $belongs_to[] = $this->resolve_belongs_to($name, $column);
                } catch (Throwable $e) {
                    throw new InvalidConfig(
                        "Unable to apply $owner->activerecord_class::belongs_to($column->associate::$name)",
                        previous: $e
                    );
                }
            }

            $has_many = [];

            foreach ($association->has_many as $item) {
                try {
                    $has_many[] = $this->resolve_has_many($owner, $item);
                } catch (Throwable $e) {
                    throw new InvalidConfig(
                        "Unable to apply $owner->activerecord_class::has_many($item->associate)",
                        previous: $e
                    );
                }
            }

            $associations[$activerecord_class] = new Association(
                belongs_to: $belongs_to,
                has_many: $has_many,
            );
        }

        return $associations;
    }

    /**
     * Creates foreign keys from the {@see Schema\BelongsTo} columns that define an action on delete.
     *
     * The columns are aligned on the size and signedness of the referenced primary key, as MySQL
     * requires.
     */
    private function resolve_foreign_keys(): void
    {
        foreach ($this->model_definitions as $definition) {
            $schema = $definition->schema;
            $columns = $schema->columns;
            $foreign_keys = $schema->foreign_keys;

            foreach ($schema->belongs_to_iterator() as $name => $column) {
                if (!$column->on_delete) {
                    continue;
                }

                try {
                    [ $columns[$name], $foreign_keys[] ] = $this->resolve_foreign_key($definition, $name, $column);
                } catch (Throwable $e) {
                    throw new InvalidConfig(
                        "Unable to create foreign key $definition->activerecord_class::$name",
                        previous: $e
                    );
                }
            }

            if ($foreign_keys === $schema->foreign_keys) {
                continue;
            }

            $definition->schema = new Schema(
                columns: $columns,
                primary: $schema->primary,
                indexes: $schema->indexes,
                foreign_keys: $foreign_keys,
            );
        }
    }

    /**
     * @param non-empty-string $name
     *
     * @return array{ Schema\BelongsTo, Schema\ForeignKey }
     */
    private function resolve_foreign_key(
        TransientModelDefinition $definition,
        string $name,
        Schema\BelongsTo $column,
    ): array {
        $on_delete = $column->on_delete;
        assert($on_delete !== null);

        $associate = $this->model_definitions[$column->associate]
            ?? throw new InvalidConfig("$column->associate is not defined");

        $associate->connection === $definition->connection
        or throw new InvalidConfig(
            "$associate->activerecord_class uses connection '$associate->connection',"
            . " a foreign key cannot reference a table on another connection"
        );

        $references = $associate->schema->primary;

        is_string($references)
        or throw new InvalidConfig("The primary key of $associate->activerecord_class is not a single column");

        $referenced_column = $associate->schema->columns[$references];

        $referenced_column instanceof Integer
        or throw new InvalidConfig("The primary key of $associate->activerecord_class is not an integer");

        ($on_delete !== Schema\OnDelete::SetNull || $column->null)
        or throw new InvalidConfig("The column must be nullable to use OnDelete::SetNull");

        return [
            new Schema\BelongsTo(
                associate: $column->associate,
                size: $referenced_column->size,
                unsigned: $referenced_column->unsigned,
                null: $column->null,
                unique: $column->unique,
                as: $column->as,
                on_delete: $on_delete,
            ),
            new Schema\ForeignKey(
                column: $name,
                table: $associate->table_name,
                references: $references,
                on_delete: $on_delete,
            ),
        ];
    }

    private function resolve_parent_definition(TransientModelDefinition $definition): ?TransientModelDefinition
    {
        $parent_class = get_parent_class($definition->activerecord_class);

        if ($parent_class === ActiveRecord::class) {
            return null;
        }

        return $this->model_definitions[$parent_class]
            ?? throw new InvalidConfig(
                "$definition->activerecord_class extends $parent_class but there's no definition for it"
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
     * @param non-empty-string $local_key
     */
    private function resolve_belongs_to(string $local_key, Schema\BelongsTo $column): BelongsToAssociation
    {
        $associate = $this->model_definitions[$column->associate]
            ?? throw new InvalidConfig("$column->associate is not defined");

        $associate->schema->has_single_column_primary
        or throw new InvalidConfig(
            "The primary key of $associate->activerecord_class is not a single column"
        );

        $foreign_key = $associate->schema->primary;
        assert(is_string($foreign_key));
        $as = $column->as ?? trim_suffix($local_key, self::ID_SUFFIX);

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
        $owner->schema->has_single_column_primary
        or throw new InvalidConfig(
            "The primary key of $owner->activerecord_class is not a single column"
        );

        $related = $this->model_definitions[$association->associate];
        $foreign_key = $association->foreign_key;
        $as = $association->as ?? Inflector::pluralize($related->alias);

        if ($association->through) {
            $foreign_key ??= $related->schema->primary;
        } elseif ($foreign_key) {
            $related->schema->has_column($foreign_key)
            or throw new InvalidConfig("$related->activerecord_class has no column '$foreign_key'");
        } else {
            $foreign_key = $this->resolve_has_many_foreign_key($owner, $related);
        }

        $foreign_key or throw new InvalidConfig("Unable to resolve the foreign key");
        is_string($foreign_key) or throw new InvalidConfig("The foreign key is not a single column");

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
     * Resolves the foreign key of a has-many relation from the {@see Schema\BelongsTo} column of the
     * related record that references the owner, or one of its ancestors.
     *
     * @return non-empty-string
     */
    private function resolve_has_many_foreign_key(
        TransientModelDefinition $owner,
        TransientModelDefinition $related,
    ): string {
        $exact = [];
        $inherited = [];

        foreach ($related->schema->belongs_to_iterator() as $name => $column) {
            if ($column->associate === $owner->activerecord_class) {
                $exact[] = $name;
            } elseif (is_a($owner->activerecord_class, $column->associate, true)) {
                $inherited[] = $name;
            }
        }

        $candidates = $exact ?: $inherited;

        count($candidates) > 0
        or throw new InvalidConfig(
            "$related->activerecord_class has no BelongsTo column referencing $owner->activerecord_class,"
            . " specify foreign_key"
        );

        count($candidates) === 1
        or throw new InvalidConfig(
            "$related->activerecord_class has several BelongsTo columns referencing"
            . " $owner->activerecord_class (" . implode(', ', $candidates) . "), specify foreign_key"
        );

        return $candidates[0];
    }

    /**
     * Adds a connection definition.
     *
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
     * Adds a record definition.
     *
     * @param class-string<ActiveRecord> $record_class
     * @param class-string<Model> $model_class
     * @param class-string<Query> $query_class
     * @param non-empty-string|null $table_name
     * @param non-empty-string|null $alias
     * @param (Closure(SchemaBuilder): SchemaBuilder)|null $schema_builder
     * @param (Closure(AssociationBuilder): AssociationBuilder)|null $association_builder
     * @param non-empty-string $connection
     */
    public function add_record(
        string $record_class,
        string $model_class = Model::class,
        string $query_class = Query::class,
        ?string $table_name = null,
        ?string $alias = null,
        ?Closure $schema_builder = null,
        ?Closure $association_builder = null,
        string $connection = Config::DEFAULT_CONNECTION_ID,
    ): self {
        Assert::extends_activerecord($record_class);

        [ $inner_schema_builder, $inner_association_builder ] = $this->create_builders($record_class);

        // schema

        if ($schema_builder) {
            $schema_builder($inner_schema_builder);
        } elseif ($this->use_attributes && $inner_schema_builder->is_empty()) {
            throw new LogicException("The Schema built from `$record_class` attributes is empty");
        }

        // association

        $schema = $inner_schema_builder->build();

        if ($association_builder) {
            $association_builder($inner_association_builder);
        }

        $this->association[$record_class] = $inner_association_builder->build();

        // transient model

        $table_name ??= self::resolve_table_name($record_class);

        $this->model_definitions[$record_class] = new TransientModelDefinition(
            schema: $schema,
            model_class: $model_class,
            activerecord_class: $record_class,
            query_class: $query_class,
            table_name: $table_name,
            alias: $alias ?? Inflector::singularize($table_name), // @phpstan-ignore-line
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

        return Inflector::pluralize(Inflector::underscore($base)); // @phpstan-ignore-line
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
     * If attributes are enabled, they are configured using the attributes on the ActiveRecord.
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
