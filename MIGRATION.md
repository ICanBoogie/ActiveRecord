# Migration

## v5.x to v6.0

### New Requirements

- PHP 8.1+

### New features

- Added interface `ModelProvider`. Better use this one than depend on `ModelCollection`. `ModelCollection` implements `ModelProvider`.
- Added interface `ModelResolver`.
- Added `StaticModelResolver` which replaces `StaticModelProvider` in `ActiveRecord`.
- Added `SchemaBuilder` to build schema using a fluent API.
- Added `through` option for `has_may` relationship.
- Added a config builder.

### Backward Incompatible Changes

- `ActiveRecord` is now abstract and requires extension.
- `Table` requires a `Connection` instance in its constructor, the `CONNECTION` attribute is no longer replaced by a `Connection` instance.
- Replaced attributes arrays to initialize tables and models with objects.
- Changed `Query::join()` and `Model::join()` signatures to enforce types.
- The `ActiveRecord` class is now defined by the `Model` instead of provided by `ModelDefinition`. The `activerecord_class` directive has been removed.
- The `Query` class is now defined by the `Model` instead of provided by `ModelDefinition`. The `query_class` directive has been removed.
- The parent model is now resolved by PHP inheritance. The `extends` directive has been removed.
- Removed support for `implements` in `Table`.

### Deprecated Features

None

### Other Changes

- `ActiveRecord` uses `StaticModelResolver` to obtain its model.
- For models extending other models, the primary key is now inherited during config building instead of resolved during `Table` constructor.
