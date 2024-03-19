# Migration

## v5.x to v6.0

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

### Deprecated Features

None

### Other Changes

- `ActiveRecord` uses `StaticModelProvider` to obtain its model.
- For models extending other models, the primary key is now inherited during config building instead of resolved during `Table` constructor.
