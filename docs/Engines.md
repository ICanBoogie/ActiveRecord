# Database engines

The package supports SQLite, MySQL, and PostgreSQL. The driver is resolved from the prefix of the
DSN: `sqlite`, `mysql`, or `pgsql`. Most features work the same on the three engines, this page
lists the differences.



## Connections

- The charset of the connection is set with `SET NAMES`, and its time zone with `time_zone`, on
  MySQL only. The `{charset}` and `{collate}` placeholders are replaced on all the engines.
- Foreign keys are enforced on all the engines. SQLite doesn't enforce them by default, the
  connection enables them with `PRAGMA foreign_keys = ON`.



## Schema

- PostgreSQL has no tiny or medium integers. `Integer::SIZE_TINY` and `Integer::SIZE_MEDIUM` throw
  an exception when a table is created, and so do serials of these sizes.
- PostgreSQL has no unsigned integers, `unsigned` is ignored.
- PostgreSQL ignores the sizes of `Text`. Blobs are `BYTEA`, binaries are `BIT` or
  `BIT VARYING`, and date times are `TIMESTAMP`.
- SQLite is lenient with types, it accepts values that other engines reject. Passing on SQLite
  doesn't prove that a schema works elsewhere.



## Queries

- The query interface quotes identifiers for the engine. In raw SQL, such as the conditions of
  `where()` or the joins of `join(expression: …)`, don't quote identifiers with backticks, which
  PostgreSQL doesn't support. Use `Connection::quote_identifier()` when an identifier needs
  quoting.
- The arguments of a statement are passed as strings. SQLite converts a string compared with a
  column to the type of the column, but compares it as text with an expression such as
  `COUNT(nid)` or `views + 1`, and a number is always less than a text. Such conditions give wrong
  results: write the value in the condition instead, or cast the argument, e.g.
  `CAST(? AS INTEGER)`.
- Ordering by the values of a column, with `order('nid', [ 2, 1 ])`, uses `FIELD()`, which is
  only available on MySQL.
- Deleting with `delete()` from a query with `take()`, or with joins, is only supported by MySQL.
- `Model::insert()` with `ignore: true` also ignores other errors on MySQL, such as data
  truncation.



## Testing on the three engines

The tests, including the examples of this documentation, run on SQLite by default. See
[tests/README.md](../tests/README.md) to run them on MySQL and PostgreSQL.
