# ADR 0002: Relations are eager loaded with `Query::includes()`

- Status: Proposed
- Date: 2026-10-30



## Context

Reading a relation of each record of a list runs one query per record, the N+1 problem. An admin
list of photo sessions that shows the name of their school is the typical case:

```php
foreach (PhotoSession::query()->order('label')->all as $photo_session) {
    echo $photo_session->school->name; // one query per session
}
```

Today there is no way to avoid it:

- `Query::join()` adds a `JOIN` for filtering and ordering, but selects the columns of the queried
  record only, so the joined record is not hydrated.
- A relation is a prototype getter (`Relation::alter_prototype()` sets `get_$as` to the relation),
  and `Relation::__invoke()` queries every time it is called. Nothing is cached on the record, so
  reading `$photo_session->school` twice runs two queries.
- The `BelongsTo` setter (`set_school`) only copies the foreign key, it can't be used to hand an
  already loaded record to the owner.
- `Model::find_many()` fetches the related records in one query, but the caller then has no way to
  attach them to the owners.

The workaround is to load the related records by hand, key them, and pass them next to the
records, which every caller has to repeat and which leaks into the signatures.

Rails solves this with `includes`, `preload` and `eager_load`: the query names the relations it
will need, the related records are loaded in a fixed number of queries, and the relation getter
reads them from the record.

The shape of the relations constrains the solution:

- `belongs_to` and `has_one` return a record (or `null`).
- `has_many` returns a `Query`, which callers chain (`->where()`, `->count`, `->order()`). A
  preloaded collection has no query behind it.
- `has_many` and `has_one` support `through` a pivot.



## Decision

Add `Query::includes(string ...$relations)`. It takes relation names, as declared on the model
(`'school'`), and returns the query, like the other builders: a `Query` is mutable, `where()` and
the others return `$this`, and so does `includes()`.

The names are validated when `includes()` is called, not when the query runs, so that a mistake
fails where it is written:

- A name is looked up in the `RelationCollection` of the model, then of its parents. Relations
  aren't inherited by `RelationCollection`: `Article` extends `Node`, a relation declared on `Node`
  is only in the collection of the `Node` model, although `$article->user` works through the
  prototype. Without climbing the parents, `Article::query()->includes('user')` would throw
  `RelationNotDefined` for a relation that exists. `RelationNotDefined` is thrown when no model of
  the chain has it. This was checked with a parent that has a `belongs_to` and a child: the model
  of the child doesn't have the relation, while `$child->maker` resolves it.
- A relation that can't be preloaded yet (see the first scope below) is refused with
  `RelationNotPreloadable`, instead of silently falling back to the lazy getter, which would hide
  the N+1 that `includes()` was meant to remove.

`RelationNotPreloadable` follows `RelationNotFound`: it extends `LogicException`, implements the
library's `Exception` marker interface, and has the relation as a `public readonly` property
(`Relation $relation`), from which the message names the owner class and the relation name. It is
listed in `docs/ActiveRecord/Exception.md` with the other exceptions. It signals a limit of the
library, not bad data, so it is a logic error that is fixed in the code that builds the query.

To know that before the query runs, `Relation` has `is_preloadable(): bool`, `false` by default,
which `BelongsToRelation` and `HasOneRelation` without `through` override with `true`. `includes()`
checks it. `Relation::preload()` is concrete and throws `RelationNotPreloadable` unless a subclass
overrides it, as a safety net for a direct call.

`Query::all()` is the single trigger. Once the records are fetched, it hands them to a private
method that preloads each requested relation: one extra query per relation, whatever the number of
records. The other ways to read a list go through `all()`, so they preload without more code: the
`$all` property, `getIterator()` (once per batch), `Model::find_many()`, and the query of a
`has_many` relation. It is the behavior of Rails' `preload`: separate queries, not a `JOIN`,
because that keeps the base query, its ordering, and its pagination untouched, and doesn't
multiply rows for collections.

**`one()` and the aggregates ignore `includes()`.** With a single record there is nothing to
batch: preloading costs the same number of queries as reading the relation lazily. Supporting it
would add a second trigger, and a second semantic, for no gain. Ignoring it is harmless, unlike on
`all()`: no N+1 can hide behind it. It also lets code that builds a query, then ends it with
`all()` or `one()` depending on the case, work in both. `count()`, `exists()`, `sum()`, and the
other computations don't hydrate records, so they ignore it too.

**Preloading is a method of the relation.** `Relation` gets
`preload(array $records): void`, next to `__invoke()`, because it is the class that knows
`local_key`, `foreign_key`, and `as`. For `BelongsTo`, it collects the distinct local keys, runs one
query on the related model filtered on them (`where([ $foreign_key => $keys ])`, which renders an
`IN (...)`), and attaches each record to its owner. `HasOne` does the same grouped on the foreign
key, keeping the first row of each owner, as the lazy getter keeps the first row of its `LIMIT 1`:
that there is at most one is up to the schema.

The preloaded relation must behave like the lazy one, so `preload()` only stores what the lazy
getter would have returned:

- `BelongsToRelation::__invoke()` returns `null` for an empty key on a nullable column, which costs
  no query, and throws `RecordNotFound` through `find_one()` for a key that matches no row. Owners
  with an empty key get no entry. A key that matches no row gets no entry either, so the getter
  falls back to the lazy path and throws the same exception. A query on the related model is used,
  rather than `find_many()`, only so that one dangling key doesn't make the whole preload throw.
- `HasOneRelation::__invoke()` returns `null` when there is no row. That is stored, otherwise the
  owner would query on every read.

`preload()` skips the owners that already have the relation loaded and fresh (see the rules below),
and runs no query when no key remains. That guard is needed, not only an optimization: an empty
list renders as `IN()`, which isn't valid SQL. Calling `includes()` twice, or on records that were
already preloaded, doesn't fetch again, and the `IN (...)` list only holds keys that are missing.
Within one preload, each related record is fetched once and shared by every owner that references
it. The values of the list are written into the statement (numbers as is, the rest quoted), so its
length grows with the number of keys. The batches of `getIterator()` bound it for big queries, and
chunking isn't needed for a first version.

**The relation stores what was loaded.** Each `Relation` instance, one per model and relation
name, holds a `WeakMap` from the owner record to the loaded value and the key it was loaded for. The
map is a property of a `readonly` class, which fixes the reference to the map, not its content.
`preload()` fills it, and the getter reads it before querying. `ActiveRecord` is not touched.

**`Relation::__invoke()` stays abstract.** It is the getter that the prototype calls, and it is
public API for anyone who extends `Relation`, so its name and contract don't change: it still takes
the owner record and returns the related record, or a `Query`. The check of the entry is not
moved into a template method that would turn `__invoke()` into a final wrapper, which would break
those subclasses. Instead `Relation` gets protected helpers that read and write the map
(`has_loaded()`, `set_loaded()`, and `read_key()`, see the implementation notes below), and
the concrete relations that support preloading call the first one before they query:

```php
public function __invoke(ActiveRecord $record): ?ActiveRecord
{
    if ($this->has_loaded($record, $value)) { // fresh entry: same key as now
        return $value;
    }

    // … the lazy path, as before
}
```

`has_loaded()` returns whether a fresh entry exists and gives its value by reference, so that a
loaded `null` can be told from a missing entry. `preload()` writes through the other helper. A
relation that doesn't support preloading, `has_many` and the `through` variants, doesn't call
either, and its `__invoke()` is unchanged. Subclasses outside the library keep working untouched:
without the call they are lazy, as today.

Three rules keep the entries honest:

- A loaded `null` is different from not loaded, so a `has_one` without a row doesn't trigger a
  query per owner afterwards. An entry is only absent when the relation was not loaded.
- The stored key is compared with the current value of `local_key` on read. If someone assigned
  `$photo_session->school_id` since, the entry is stale and the getter queries. Rails does the
  same check. Plain property assignment can't be hooked, so comparing is the only reliable
  invalidation.
- The entries are keyed by record identity and die with the record. Nothing is serialized, since
  the state isn't a property of the record, and a cloned record has no entry, so it queries
  rather than sharing a snapshot with the original.

**`all()` with `includes()` is for records only.** It is rejected with a `LogicException` when the
fetch mode isn't `PDO::FETCH_CLASS`, which is the case with `select()` (the default mode then is
`PDO::FETCH_ASSOC`, see `resolve_fetch_mode()`) and with an explicit mode such as
`PDO::FETCH_COLUMN`, since there is no record to attach to. Failing visibly is consistent with the
exact-match philosophy of [ADR 0001](0001-custom-columns-with-into-column.md): a silently ignored
`includes()` on a list is a N+1 that nobody sees.

The includes are a plain array on the query, so that `clone`, which `one()` and `getIterator()`
rely on, copies them.

**First scope: `belongs_to` and `has_one`**, single level. They return a record, so the loaded
value and the lazy value have the same type, and the contract of the getter doesn't change.
`has_many`, the `through` variants of `has_one`, and nested includes are deliberately left out, see
below. `includes()` refuses the relations with `RelationNotPreloadable`, and a nested name such as
`'school.region'` isn't a relation name, so it fails with `RelationNotDefined`.

**Implementation notes.** What the implementation settled differently, or in more detail, than
the sketches above:

- `has_loaded()` and `set_loaded()` are typed on `?ActiveRecord`, not `mixed`: with `belongs_to`
  and `has_one` the value is a record or `null`, and it avoids casts. A future `has_many` preload
  stores a `Query` or a list, and will have to widen the type.
- `read_key()` is a third helper, shared by `preload()` and the freshness check. It reads the
  value of a key column from a record, and returns `null` for an unset or empty (`''`) key. A key
  of `0` is returned as is, but the lazy getter rejects it (`!$id`), so the preload skips the
  owners with a `0` key too, to behave the same.
- The check that includes need a record fetch mode masks the mode with `0xFFFF`, so that
  `PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE` is accepted: the flags are in the high bits. It runs
  once the statement was executed, because both paths of `all()` share the fetch. A rejected call
  costs one wasted query, for what is a programming error.
- `Query::all()` returns `array<mixed>`. The path with includes narrows the fetched records to
  `ActiveRecord[]` with an inline `@var`, as `HasOneRelation::__invoke()` already does for `one`.



## Consequences

Positive:

- Removes the N+1 for the most common case, a list of records that show their parent, with one
  query per relation instead of one per record.
- The getter doesn't change: `$photo_session->school` reads the same, loaded or not. Code that
  doesn't call `includes()` is untouched.
- Repeated reads of a loaded relation stop querying.
- Small, additive API: one builder method, one method on `Relation`.
- The query count of a list becomes testable, which the tests of the feature do.

Negative:

- `includes()` on a `has_many` is not supported yet, and a list of records with their collection
  still has the N+1. Asking for an unsupported relation throws instead of degrading.
- The loaded value is not visible on the record: `var_dump($record)` doesn't show it, which makes
  debugging a little harder.
- A `readonly` class that mutates through a `WeakMap` may surprise a reader. The state is per
  process, tied to the relation instances of the model registry, and goes away with them.
- The record, the getter, and `includes()` must agree on the `Relation` instance. The getter on the
  prototype is the last `Relation` built for the class, because `alter_prototype()` overwrites it,
  while `includes()` resolves the relation from the model of the query. With one model registry
  per process they are the same, which the prototype getters already assume. Tests that build a
  new set of models per test, as the suite does with `Fixtures::only_models()`, build their records
  and queries from that set, and are fine too. A record fetched by one registry and read after
  another one was built just falls back to the lazy getter, it doesn't misbehave.
- A loaded relation is a snapshot. If the related record changes in the database after the
  preload, the owner still sees the old one, as with any loaded record.
- Preloading `through` relations needs a pivot join that also selects the pivot key, which
  `HasRelation::build_through_query()` doesn't do today.



## Alternatives considered

- **`eager_load` with a single `JOIN`.** Hydrating both records from one result set takes
  aliased columns for the joined record on top of the owner's, a hydration step that splits them,
  and it multiplies rows for collections. It also changes the shape of the base query, which
  `select()`, `take()`, and `skip()` depend on. Separate queries are simpler and good enough: the
  count is constant. A `JOIN`-based variant can be added later for `belongs_to`.
- **Cache the relation on first read, with no `includes()`.** Fixes the repeated read, not the N+1
  across records, which is the problem.
- **Let callers load and pass the related records.** What applications do today. It spreads the
  keying and attaching code, and puts related records in method signatures.
- **Reuse the related records already held by other owners.** It would shorten the `IN (...)`
  list further, but the map is keyed by owner, so finding a related record by key means scanning
  every live entry on each preload to rebuild an index. That index is an identity map in all but
  name, and serves a record loaded earlier as if it were current. The gain is a shorter list, not a
  smaller number of queries, which is already constant.
- **A template method, `__invoke()` final and a new abstract method for the lazy path.** One place
  checks the entry, and no relation can forget it. But it renames the contract of a public abstract
  method, which breaks subclasses outside the library for a gain of two lines per relation. A
  relation that forgets the call is lazy, which is the current behavior, and the tests of each
  preloadable relation catch it.
- **A private map on `ActiveRecord`**, relation name to loaded value. The loaded value is visible
  when dumping the record, and there is no mutation through a `readonly` class. But it spreads the
  feature over two classes, adds state to every record, and has to be excluded from `__sleep()`
  and decided on for `clone`, which would otherwise copy a snapshot to the clone.
- **A public setter for the loaded relation.** Lets callers attach records themselves, but exposes
  the storage and its invalidation rule, and the `BelongsTo` setter already means "change the
  foreign key".
- **Make `has_many` return a collection proxy now.** Rails' answer, but the proxy has to serve
  loaded data and still behave like a `Query` for `->where()` and `->count`. That is an API
  design of its own, out of proportion for a first step.
- **Preload `has_many` as a plain array.** Simple, but a loaded `$post->comments` would return an
  array and an unloaded one a `Query`, which breaks callers that chain.
- **Automatic loading, with no `includes()`.** `all()` registers the fetched records as a cohort
  (held through `WeakReference`s), and a relation getter that misses preloads the relation for every
  owner of the cohort that doesn't have it yet, with the same `preload()` and skip rule. The caller
  doesn't have to know the query site, which is the case when a store runs the query and a
  presentation layer reads the relation, and a relation that is read in some branches only is loaded
  only then. It is not the default for these reasons:
  - The query count follows the access pattern, and the first access runs a batch query that the
    code doesn't show.
  - Touching one record of 500 loads the relation for all of them, unless the batch is capped.
  - Every list pays for a cohort and a reference per record, even when no relation is read.
  - The batch is read at the first access, not when the query ran, so the related rows may have
    changed in between, which matters in long transactions and workers.
  - It can't cover `has_many`: loading it implicitly would change the return type of
    `$post->comments`, which callers chain. `includes()` is still needed for it, for nested
    relations, and when the count must be deterministic.

  It reuses everything this ADR adds, so it can come later, see below.



## Revisit when

- `has_many` preloading is needed. Decide the return type first: a `Query`-like wrapper over the
  loaded records, or a separate accessor for the loaded collection.
- Nested includes are needed (`'school.region'`). The relation names would be resolved through the
  related model, and the preload run per level.
- A `JOIN` based `eager_load` is wanted, for a `belongs_to` filtered on the related table.
- Callers keep hitting the N+1 because the query site can't know what will be read. Then add
  automatic loading, opt-in per query, per model, or in the config, never on by default. Cap the
  batch size, for example at the batch size of `getIterator()`, don't register a cohort for
  `one()`, and keep it to the relations that return a record.
