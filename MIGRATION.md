# Migration

## v5.x to v6.0

### New Requirements

- PHP 8.1+

### New features

- Added interface `ModelProvider`. Better use this one than depend on `ModelCollection`. `ModelCollection` implements `ModelProvider`.
- Added interface `ModelResolver`.
- Added `StaticModelResolver` which replaces `StaticModelProvider` in `ActiveRecord`.

### Backward Incompatible Changes

None

### Deprecated Features

None

### Other Changes

- `ActiveRecord` uses `StaticModelResolver` to obtain its model.
