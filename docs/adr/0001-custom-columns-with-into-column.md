# ADR 0001: Custom columns resolve through `IntoColumn`

- Status: Accepted
- Date: 2026-10-06 (`ResolvesToColumn` added), 2026-10-16 (renamed `IntoColumn`)



## Context

Applications want domain-specific column types, such as `#[Uuid]`, that read well on a record
property but are stored as one of the basic types. The basic types are the column attributes of
`ICanBoogie\ActiveRecord\Schema`: `Boolean`, `Integer`, `Decimal`, `Character`, `Binary`, `Text`,
`Blob`, and so on.

The table renderers (`Driver/TableRendererFor{SQLite,MySQL,PostgreSQL}`) map a column to a SQL type
with `match ($column::class)` in `TableRenderer::render_type_name()`. An exact class match is
deliberate: it can't be fooled by inheritance, and it fails visibly on an unknown column. But it
means a subclass of `Character` isn't rendered as a `Character`.

Custom columns must work both as attributes on record classes and with the fluent `SchemaBuilder`
and `ConfigBuilder` APIs.



## Decision

A custom column extends one of the basic column attributes and implements `IntoColumn`:

```text
interface IntoColumn { public function into_column(): Column; }
```

`TableRenderer::render_type_name()` calls `into_column()` before the `match`, so renderers only ever
see basic columns. The custom class carries the domain meaning, and the basic column carries the
storage.

Extending a basic column keeps the custom class usable wherever a `Column` is expected, and lets it
reuse the parent's constructor and properties (`null`, `unique`, `default`, …).

The interface was first named `ResolvesToColumn`, and renamed `IntoColumn` to follow PHP's and Rust's
`into_*` convention for a consuming conversion.



## Consequences

Positive:

- Small API: one interface, one method. Documented in `docs/ActiveRecord/Schema.md`.
- Works for attributes and builders alike, since it operates on `Column` instances.
- The three renderers need no change, and the exact-class `match` keeps its safety.
- The custom column can resolve to a type unrelated to its parent, but the parent is a convenient
  default.

Negative:

- Often redundant. `Uuid` already configures its parent as a fixed `Character`, so `into_column()`
  mostly copies it into a plain `Character`.
- The conversion is applied only in `render_type_name()`. Any other code that inspects columns, such
  as `instanceof Integer` checks or default rendering, sees the unconverted subclass.
- No per-engine variation: a custom column can't render as native `UUID` on PostgreSQL and
  `CHAR(36)` elsewhere, because `into_column()` doesn't know the driver and the result is limited to
  the basic types.



## Alternatives considered

- **Match with `instanceof`, no interface.** Least API, but inheritance makes the renderers fragile
  (see below), a subclass can't map to a different type, and the subclass leaks into anything that
  checks `::class`.
- **Driver-aware conversion, `into_column(Driver $driver)`.** Allows engine-specific types, but ties
  a column to a driver and still can't express types that have no basic column.
- **Type registry on the driver or config**, mapping a column class to a SQL renderer. Keeps columns
  as plain data and is overridable, but takes more wiring, and splits the attribute from its SQL.
- **Raw SQL type escape hatch**, such as `#[Custom(type: 'UUID')]`. Simple, but not portable, and at
  odds with defaults being plain values rather than SQL.
- **Static factories only**, such as `Character::uuid()`. Not usable as attributes, which breaks the
  rule that attributes and builders both work.



## Why inheritance alone is painful

The column hierarchy is already not flat: `Serial` and `BelongsTo` extend `Integer`. That is the
case that shows what `instanceof` dispatch would cost.

- **Order becomes load-bearing.** `TableRendererForSQLite::render_type_name()` maps `Serial` and
  `BelongsTo` to `INTEGER`, and `Integer` to `INTEGER($size)`. With `::class` the order of the arms
  is irrelevant. With `instanceof`, `Integer` before `Serial` silently renders a sized
  `INTEGER($size)` for a serial column, which the comment in that renderer says SQLite doesn't like,
  and no code path fails loudly.
- **Every custom subclass joins the problem.** A `#[Uuid]` extending `Character` is fine today. The
  day someone extends `Integer` for a `#[Money]` or `#[Year]`, it must be placed relative to
  `Serial`, `BelongsTo`, and any other subclass, in each of the three renderers, each with its own
  `match`.
- **The exact match is the safety net.** An unknown class hits
  `default => throw new RuntimeException("Don't know what to do with …")`. With `instanceof`, an
  unrelated subclass is quietly rendered as its parent, whether or not that was intended.
- **Subclasses already get parent behavior elsewhere.** Several checks are already `instanceof`:
  `TableRenderer::render_default()` (numeric defaults on `Integer` or `Decimal`),
  `TableRendererForMySQL` (`instanceof Integer`), and `ConfigBuilder` (`instanceof Integer` for
  foreign key alignment). A custom `Integer` subclass is therefore treated as an integer there
  whatever it resolves to. `IntoColumn` doesn't fix those checks, but it confines the problem to
  them and keeps type dispatch exact.
- **Each engine repeats the ordering.** The same subclass-before-parent rule must be kept in
  every renderer that overrides `render_type_name()`, and a mistake in one only shows on that
  engine's test run.

`IntoColumn` sidesteps all of this: after the conversion, the renderer sees only the exact basic
classes, and the question "which parent does this subclass most resemble?" never comes up.



## Revisit when

- A custom column needs an engine-specific type. Then prefer a driver-aware conversion or a
  registry.
- Other code needs the resolved column. Then resolve once, in `ConfigBuilder` or `Schema`,
  rather than in the renderer.
