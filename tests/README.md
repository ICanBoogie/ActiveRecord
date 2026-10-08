# Tests

This directory contains the test suite. Most tests run against [SQLite][] out of the box, but the
suite is designed to also run against [PostgreSQL][] and [MySQL][].

## Running the tests

```shell
make test        # the full suite, against SQLite (the default)
make test-db     # only the tests tagged with the "db" group
make test-coverage
make lint        # PHPStan
```

Tests tagged with `#[Group("db")]` really touch the database. The rest are unit tests that never
open a connection.

To run the database tests inside the provided Docker containers:

```shell
make test-container-db-sqlite
make test-container-db-pgsql
make test-container-db-mysql
```

These start the appropriate database service and run `--group db` against it.

## Configuring the connection

The tests read the connection from environment variables, so the same suite can be pointed at any
engine:

| Variable                | Description                                    | Default               |
|-------------------------|------------------------------------------------|-----------------------|
| `ACTIVERECORD_DSN`      | The PDO DSN.                                   | `sqlite::memory:`     |
| `ACTIVERECORD_USERNAME` | The username, if the engine requires one.      | none                  |
| `ACTIVERECORD_PASSWORD` | The password, if the engine requires one.      | none                  |

For example:

```shell
# PostgreSQL
ACTIVERECORD_DSN='pgsql:host=127.0.0.1;dbname=postgres' \
ACTIVERECORD_USERNAME=postgres \
ACTIVERECORD_PASSWORD=postgres \
vendor/bin/phpunit --group db

# MySQL
ACTIVERECORD_DSN='mysql:host=127.0.0.1;dbname=activerecord' \
ACTIVERECORD_USERNAME=root \
ACTIVERECORD_PASSWORD=root \
vendor/bin/phpunit --group db
```

The values are resolved by `Test\ICanBoogie\Fixtures` and are used by every test that touches the
database. SQLite needs no username or password, which is why `sqlite::memory:` keeps working as a
zero-configuration default.

## Groups

- `db` — tests that really touch the database. This is the group to run against each engine.
- `sqlite`, `pgsql` — engine-specific tests (e.g. `StatementTest` asserts SQLite error messages;
  `PostgreSQLTest` asserts PostgreSQL boolean handling). Engine-specific tests either hardcode
  their own DSN or skip themselves when the configured DSN doesn't match.
- `validate`, `record` — additional feature-specific groups, used alongside `db` when relevant.

## Test isolation

SQLite's `sqlite::memory:` gives every connection a fresh database, but PostgreSQL and MySQL
databases are shared and persistent. To keep tests independent, tests that create tables extend
`Test\ICanBoogie\DbTestCase`, which drops every table in the current database after each test.

[SQLite]: https://www.sqlite.org/
[PostgreSQL]: https://www.postgresql.org/
[MySQL]: https://www.mysql.com/
