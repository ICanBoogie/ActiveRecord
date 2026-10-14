# AGENTS.md

Guidance for AI coding agents working on `icanboogie/activerecord`, an ActiveRecord ORM for PHP
built on PDO. It supports SQLite, MySQL, and PostgreSQL.

## Requirements

- PHP 8.4+ (CI runs 8.4 and 8.5). The code uses 8.4 features: property hooks, asymmetric
  visibility (`private(set)`), and `new Foo()->bar()` without parentheses.
- Extensions: `pdo`. Install `pdo_mysql` and `pdo_pgsql` too; they are dev requirements.

## Layout

| Path                         | Contents                                                                                                       |
|------------------------------|----------------------------------------------------------------------------------------------------------------|
| `lib/ActiveRecord.php`       | The `ICanBoogie\ActiveRecord` base class for records.                                                          |
| `lib/ActiveRecord/`          | `ICanBoogie\ActiveRecord\*`: Connection, Model, Table, Query, Schema, relations, providers, exceptions.        |
| `lib/ActiveRecord/Config/`   | Config definitions (connections, tables, models, associations).                                                |
| `lib/ActiveRecord/Driver/`   | One driver and table renderer per engine (SQLite, MySQL, PostgreSQL).                                          |
| `lib/ActiveRecord/Schema/`   | Column and relation attributes (`#[Integer]`, `#[BelongsTo]`, …), plus `ResolvesToColumn`.                     |
| `lib/ActiveRecord/Property/` | Traits for date properties, implemented with property hooks.                                                   |
| `helpers.php`                | Functions that Composer autoloads.                                                                             |
| `tests/`                     | `Test\ICanBoogie\*`. Fixture records live in `tests/Acme/`.                                                    |
| `docs/`                      | The documentation, one page per topic, laid out like `lib/`. `README.md` only has meta sections and the index. |

## Commands

```shell
composer install
make test                      # full suite, SQLite in memory by default
make test-db                   # only #[Group("db")] tests
make lint                      # PHP-CS-Fixer, then PHPStan (level max) over lib/ and tests/
vendor/bin/phpunit --filter TableTest
```

To run the DB tests against a real engine, use Docker (`make test-container-db-pgsql`,
`make test-container-db-mysql`, `make test-container-db-sqlite`) or set the environment variables
yourself:

```shell
ACTIVERECORD_DSN='pgsql:host=127.0.0.1;dbname=postgres' \
ACTIVERECORD_USERNAME=postgres ACTIVERECORD_PASSWORD=postgres \
vendor/bin/phpunit --group db
```

For the full details, see `tests/README.md`.

## Conventions

- **Methods and properties use snake_case** (`quote_identifier`, `extended_schema`,
  `add_serial`). PSR-12 applies otherwise, enforced by PHP-CS-Fixer (`.php-cs-fixer.dist.php`), which doesn't
  rename anything. Property hooks open their brace on the same line (`public int $x {`).
  Classes use PascalCase.
- Use constructor promotion, `readonly`, and named arguments, as the existing code does.
- Schemas are declared either with attributes on record classes (see `tests/Acme/`) or with the
  fluent `SchemaBuilder`/`ConfigBuilder` APIs. When you change one, keep the other working.
- Fix PHPStan errors instead of suppressing them. `lib/` has no baseline. A `@phpstan-ignore`
  needs an identifier and a comment. `phpstan-baseline.neon` only holds errors of `tests/`.
- `Model` and `Query` are generic (`TRecord`). Where the record isn't known, write `Model<*>` and
  `Query<*>`.
- `ActiveRecord::validate` is a prototype method. The application binds it;
  for the tests, `tests/bootstrap.php` does.
- Column defaults are plain values (`default: "madonna"`), not SQL. `TableRenderer::render_default()`
  quotes them. Only the `CURRENT_TIMESTAMP`, `CURRENT_DATE`, and `CURRENT_TIME` keywords,
  numbers on numeric columns, and `TRUE`/`FALSE` on `Boolean` columns are written unquoted.
- `Boolean` is not an `Integer`, so `instanceof Integer` checks don't match it.
- DDL is rendered separately for each engine, in `Driver/TableRendererFor{SQLite,MySQL,PostgreSQL}`.
  When you change one renderer, check the other two and update all three renderer tests.
- Foreign keys are opt-in through `BelongsTo::$on_delete`. `ConfigBuilder::resolve_foreign_keys()`
  turns them into `Schema::$foreign_keys`, and aligns the column on the referenced primary key.
  The renderers can't see other models, so whatever they need about the referenced table must be
  resolved into the `Schema` first.
- Foreign keys are enforced on all three engines, including SQLite, where `Connection` enables them.
  Test data must reference rows that exist.

## Tests

- Every change needs tests. PHPUnit 13 uses attributes such as `#[Group("db")]` and
  `#[DataProvider(...)]`, not annotations.
- Tag tests that open a database connection with `#[Group("db")]`, and make them extend
  `Test\ICanBoogie\DbTestCase`. It drops every table after each test, because MySQL and PostgreSQL
  databases persist between tests.
- Get connection settings from `Fixtures::dsn()`, `Fixtures::username()`, and
  `Fixtures::password()` so the same test runs on all three engines. Don't hardcode
  `sqlite::memory:` unless the test is specific to SQLite. When you build a config with
  `ConfigBuilder`, start from `Fixtures::with_main_connection()`. Passing `Fixtures::dsn()` on its
  own drops the credentials, so the test fails on MySQL and PostgreSQL.
- A test that calls `StaticModelProvider::set()` must call `StaticModelProvider::reset()` in
  `tearDown()`. Otherwise the provider leaks into later tests.
- SQLite is lenient: it accepts unquoted string defaults and loose types. Passing on SQLite doesn't
  prove that a change works elsewhere. If you change SQL generation, also run
  `make test-container-db-pgsql` and `make test-container-db-mysql`.
- Tag tests that only make sense on one engine with `sqlite` or `pgsql`. Those tests skip
  themselves when the configured DSN is for a different engine.
- SQL that a test asserts against can differ by driver. Branch on `$connection->driver_name`
  instead of assuming SQLite.

## Changes and docs

- Follow SemVer. Don't break the public API without a reason. When you do, record it under
  "Backward Incompatible Changes".
- Record user-visible changes in the current version's section of `CHANGELOG.md` (currently v7.0).
- Update `docs/` when behavior changes. Add new pages to the index in `README.md`.
- The PHP blocks of the docs are tests (`tests/Docs/DocsTest.php`), run after the setup blocks of
  `docs/GettingStarted.md`. Write examples against those records, and mark blocks that can't run
  with `<!-- doc-test: … -->`, see `tests/README.md`.
- Commit messages are short imperative subjects ("Add ResolvesToColumn", "Use PHPUnit 13").
