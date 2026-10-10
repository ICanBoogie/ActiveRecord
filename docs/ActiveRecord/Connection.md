# Connections

A [Connection][] is a connection to a SQLite, MySQL, or PostgreSQL database. It wraps a `PDO`
instance, available as `$connection->pdo`, and is created from a [ConnectionDefinition][].

The examples use the records of [Getting started](../GettingStarted.md), and `$connection` is the
`primary` connection.



## Defining connections

Connections are usually defined with `ConfigBuilder::add_connection()`:

```php
<?php

use ICanBoogie\ActiveRecord\ConfigBuilder;

$config = new ConfigBuilder()
    ->add_connection(
        id: 'primary',
        dsn: 'mysql:host=127.0.0.1;dbname=example',
        username: 'username',
        password: 'password',
        table_name_prefix: 'dev',
        charset_and_collate: 'utf8mb4/unicode_ci',
        time_zone: '+02:00',
    )
    ->add_connection('cache', 'sqlite::memory:')
    ->build();
```

- `id`: Identifies the connection. Models use the `primary` connection unless their record is
  added with another one, e.g. `add_record(Session::class, connection: 'cache')`.
- `dsn`, `username`, `password`: Passed to `PDO`. The driver is resolved from the DSN prefix:
  `sqlite`, `mysql`, or `pgsql`.
- `table_name_prefix`: Prefixes the name of every table of the connection, with an underscore. With
  the `dev` prefix, the table `nodes` is named `dev_nodes`.
- `charset_and_collate`: The charset and the collate, separated by a slash. Defaults to
  `utf8/general_ci`, for the `utf8` charset and the `utf8_general_ci` collate. With MySQL, the
  charset is set with `SET NAMES` when the connection is established.
- `time_zone`: The time zone of the connection, as an offset such as `+02:00`. Defaults to
  `+00:00`. With MySQL, it is set when the connection is established.

On SQLite, the connection enables foreign keys with `PRAGMA foreign_keys = ON`, which SQLite
doesn't do by default.



## Running statements

`query()` prepares and executes a statement, and returns a [Statement][]. `exec()` executes a
statement and returns the number of affected rows.

```php
<?php

/* @var $connection \ICanBoogie\ActiveRecord\Connection */

$titles = $connection->query('SELECT title FROM {prefix}nodes WHERE nid > ?', [ 10 ])->all;
$count = $connection->query('SELECT COUNT(*) FROM {prefix}nodes')->rc;
$connection->exec('DELETE FROM {prefix}nodes WHERE nid = 1');
```

The following placeholders are replaced in statements:

- `{prefix}`: The table name prefix, with its underscore.
- `{charset}`: The charset of the connection.
- `{collate}`: The collate of the connection.

A [StatementNotValid][] exception is thrown when a statement can't be prepared or executed.

A statement provides the following properties to fetch its result: `all` for all the rows, `one`
for the first row, `rc` for the first column of the first row, and `pairs` for key/value pairs
made of the first two columns. The `all()` and `one()` methods take a fetch mode.

Other useful members of a connection:

- `driver_name`: `sqlite`, `mysql`, or `pgsql`.
- `quote()` and `quote_identifier()`: Quote a value or an identifier for the driver.
- `table_exists()`: Whether a table exists, the name is given without the prefix.
- `begin()`: Begins a transaction, use `$connection->pdo` to commit or roll back.
- `last_insert_id`: The ID of the last inserted row.



## The connection registry

A [ConnectionRegistry][] establishes connections from their definitions, on demand. You can
define many connections, they are only established when they are needed. It implements
[ConnectionProvider][], type against the interface when you only need connections.

```php
<?php

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\ConnectionRegistry;

$connections = new ConnectionRegistry([
    new ConnectionDefinition(id: 'read', dsn: 'sqlite::memory:'),
    new ConnectionDefinition(id: 'write', dsn: 'mysql:dbname=example'),
]);

$connection = $connections->connection_for_id('read');
```

Usually, the definitions come from a config built with `ConfigBuilder`:

```php
<?php

use ICanBoogie\ActiveRecord\ConnectionRegistry;

/* @var $config \ICanBoogie\ActiveRecord\Config */

$connections = new ConnectionRegistry($config->connections);
```

The same connection is returned for the same identifier. A [ConnectionNotDefined][] exception is
thrown for an identifier that is not defined, and a [ConnectionNotEstablished][] exception when
the connection fails. The message of the latter doesn't include the DSN or the credentials.

The definitions are available with the `definitions` property, and `connection_iterator()`
iterates over them, telling which connections are established:

```php
<?php

/* @var $connections \ICanBoogie\ActiveRecord\ConnectionRegistry */

if (isset($connections->definitions['read'])) {
    echo "The connection 'read' is defined.\n";
}

foreach ($connections->connection_iterator() as $id => $accessor) {
    if ($accessor->instantiated) {
        echo "The connection '$id' is established.\n";
    }

    // $accessor->get() returns the connection, establishing it if needed.
}
```



## The static connection provider

[StaticConnectionProvider][] gives access to connections from anywhere, through a factory of
[ConnectionProvider][]. The factory is invoked once, the first time a connection is requested.

```php
<?php

use ICanBoogie\ActiveRecord\StaticConnectionProvider;

/* @var $connections \ICanBoogie\ActiveRecord\ConnectionRegistry */

StaticConnectionProvider::set(fn() => $connections);

$connection = StaticConnectionProvider::connection_for_id('primary');
```



## Telemetry

Each connection records the statements it executes in `$connection->telemetry`:
`execute_count` is the number of executions, and `record_execute_time` is a list of
[ExecuteRecord][] with the timestamp, duration, and statement of each execution.



[Connection]:                ../../lib/ActiveRecord/Connection.php
[ConnectionDefinition]:      ../../lib/ActiveRecord/Config/ConnectionDefinition.php
[ConnectionNotDefined]:      ../../lib/ActiveRecord/ConnectionNotDefined.php
[ConnectionNotEstablished]:  ../../lib/ActiveRecord/ConnectionNotEstablished.php
[ConnectionProvider]:        ../../lib/ActiveRecord/ConnectionProvider.php
[ConnectionRegistry]:        ../../lib/ActiveRecord/ConnectionRegistry.php
[ExecuteRecord]:             ../../lib/ActiveRecord/ConnectionTelemetry/ExecuteRecord.php
[Statement]:                 ../../lib/ActiveRecord/Statement.php
[StatementNotValid]:         ../../lib/ActiveRecord/StatementNotValid.php
[StaticConnectionProvider]:  ../../lib/ActiveRecord/StaticConnectionProvider.php
