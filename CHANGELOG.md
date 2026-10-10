# CHANGELOG

## v7.0

### New Requirements

PHP 8.4+

### New features

- Added `ResolvesToColumn` to enable custom column types.
- Added support for PostgreSQL.
- Added foreign key constraints, opt-in with `BelongsTo::$on_delete` and `OnDelete`, e.g.
  `#[BelongsTo(User::class, on_delete: OnDelete::Cascade)]`. `ConfigBuilder` resolves them into
  `Schema::$foreign_keys`, and `ModelInstaller::install()` creates referenced tables first.
- Added `ActiveRecord::model()` to resolve the model of a record class.
- `Boolean` columns accept a default value, e.g. `#[Boolean(default: false)]`, rendered as `TRUE`
  or `FALSE`. `SchemaBuilder::add_boolean()` takes `default` too.
- `ModelInstaller::install()` takes an optional `InstallProgress`, notified of each model as already
  installed, installing, installed, failed, or skipped because a model it depends on failed. By
  default, the first failure is thrown. Models are installed after their parent too, not only after
  the tables referenced by their foreign keys.

### Backward Incompatible Changes

- Renamed `ModelCollection` as `ModelRegistry`, and `ConnectionCollection` as `ConnectionRegistry`.
- Moved `install()`, `uninstall()`, and `is_installed()` from `ModelCollection` to the new
  `ModelInstaller`, which takes any `ModelIterator`. Replace `$models->install()` with
  `new ModelInstaller($models)->install()`.
- Removed unused `ModelAttribute`.
- Column default values are now rendered as SQL literals: strings are quoted and escaped, numbers
  of numeric columns and the `CURRENT_*` keywords are rendered as is. Defaults that were quoted by
  hand, such as `"'madonna'"`, must be unquoted.
- `Table::save()` no longer takes `$options`, which was unused, and returns `int` instead of
  `int|false`.
- `Table::insert()` throws `LogicException` when both `ignore` and `upsert` are requested.
- Removed `ActiveRecord::$model` and the `$model` parameter of the `ActiveRecord` constructor. Use
  `ActiveRecord::model()` instead, which resolves the model with `StaticModelProvider`. A model
  provider must now be defined before records are used.
- Removed `ActiveRecord::__debugInfo()`, it only existed to hide `$model`.
- Without `select()`, a query now selects only the columns of its record, e.g.
  `SELECT article.*, node.*` instead of `SELECT *`. The columns of joined tables are no longer
  hydrated into records, and no longer overwrite the record's columns of the same name. Use
  `select('*')` to get them, as arrays.
- `Boolean` no longer extends `Integer`, it extends `Column`. It doesn't have `$size` or
  `$unsigned` anymore, and MySQL no longer renders `BOOLEAN UNSIGNED`, which MySQL rejects.
- Removed `Model::new()`. Instantiate the record class instead, e.g. `new Node()`.
- With SQLite, an upsert now uses `ON CONFLICT … DO UPDATE` instead of `INSERT OR REPLACE`. Columns
  that are not provided keep their values instead of being reset, and only primary key conflicts
  trigger the update. This matches MySQL and PostgreSQL.
- Removed `ScopeNotDefine`, a leftover from scopes.
- Removed `StatementInvocationFailed` for it's never used.

### Deprecated Features

None

### Other Changes

- Date properties use property hooks.
- Remove dependency on icanboogie/common.
- `ActiveRecord::save()` returns the record.
- Fixed `ActiveRecord::delete()` for records with a multi-column primary key.
- Fixed float values being truncated to integers when saved.
- Fixed updating a record spread over multiple tables with PostgreSQL.
- Fixed PostgreSQL tables with unsigned integers, PostgreSQL has no `UNSIGNED`.
- Fixed the primary key of a child table not having the signedness of its parent's primary key. With
  MySQL, `INTEGER UNSIGNED` parents had `INTEGER` children.
- SQLite connections enable foreign keys with `PRAGMA foreign_keys = ON`.
- Saving or updating a record spread over multiple tables happens in a transaction, unless one is
  already active.
- Fixed upserts on MySQL and PostgreSQL for tables where every column is part of the primary key,
  such as join tables. The update clause used to be empty, which is invalid SQL.
- Use `Pdo\Mysql::ATTR_INIT_COMMAND` instead of `PDO::MYSQL_ATTR_INIT_COMMAND`, which is deprecated
  since PHP 8.5.
- `StaticModelProvider` now caches models per record class, and `set()` invalidates the cache.
- The documentation moved from `README.md` to `docs/`, one page per topic, and was updated for v7.0.



## v6.0

### New Requirements

- PHP 8.2+

### New features

- Added interface `ModelProvider`. `ModelCollection` implements it. Better use this one than depend on `ModelCollection`.
- Added `SchemaBuilder` to build schema using a fluent API.
- Added `through` option for `has_may` relationship.
- Added a config builder.
- The static methods `ActiveRecord::query()` and `where()` can be used to create a query directly from an ActiveRecord.

### Backward Incompatible Changes

- The `ActiveRecord` class is now abstract and require extension.
- The `Query` class is now defined by the `Model` instead of provided by `ModelDefinition`. The `query_class` directive has been removed.
- Model parents are now resolved using PHP inheritance on ActiveRecord classes. The `extends` directive has been removed.
- Models are now identified by their ActiveRecord, the `id` property has been removed.
- `Table` requires a `Connection` instance in its constructor, the `CONNECTION` attribute is no longer replaced by a `Connection` instance.
- Replaced attributes arrays to initialize tables and models with objects.
- `ConnectionCollection` and `ModelCollection` no longer implement `ArrayAccess`, and are read only. Use the `connection_for_id()` method to obtain a connection. Use the `model_for_record()` method to obtain a model.
- Removed `Model::join()`.
- Removed support for `implements` in `Table`.
- Removed `get_model()`
- Removed the notion of scopes on Model, they are better replaced with Query extensions.
- Removed forwarded query methods on the model. Still `query()` and `where()` remain available.
- The `Model` class no longer implements `ArrayAccess`.
- Metrics are now collected with `ConnectionTelemetry`.

### Deprecated Features

None

### Other Changes

- `ActiveRecord` uses `StaticModelProvider` to obtain its model.
- For models extending other models, the primary key is now inherited during config building instead of resolved during `Table` constructor.
