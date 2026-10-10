# Configuration

A [ConfigBuilder][] collects the definitions of the connections and the records, and builds a
[Config][]. The config is then used to create the [connection registry](ActiveRecord/Connection.md#the-connection-registry)
and the [model registry](ActiveRecord/Model.md#the-model-registry), see
[Getting started](GettingStarted.md#configuring-connections-and-models).

The examples use the records of [Getting started](GettingStarted.md), and `$builder` is a
`ConfigBuilder` with these records already added.



## Using attributes

`use_attributes()` builds the schemas and the associations from the attributes of the record
classes. Without it, the schemas are built with the `schema_builder` parameter of `add_record()`,
see [Schema](ActiveRecord/Schema.md).



## Adding connections

`add_connection()` adds a connection definition. Models use the `primary` connection unless their
record is added with another one. See [Connections](ActiveRecord/Connection.md#defining-connections)
for the options.



## Adding records

`add_record()` adds a record class, and defines its model:

| Parameter             | Default                         | Description                                                                 |
|-----------------------|---------------------------------|-----------------------------------------------------------------------------|
| `record_class`        |                                 | The record class, extending `ActiveRecord`.                                 |
| `model_class`         | `Model`                         | The class of the model, see [custom model classes](ActiveRecord/Model.md#custom-model-and-query-classes). |
| `query_class`         | `Query`                         | The class of the queries, see [custom query classes](ActiveRecord/Model.md#custom-model-and-query-classes). |
| `table_name`          | Plural of the class, `articles` | The name of the table, without the prefix of the connection.               |
| `alias`               | Singular of the table name      | The alias of the table, in queries.                                         |
| `schema_builder`      | `null`                          | A closure that builds the schema, see [Schema](ActiveRecord/Schema.md#building-the-schema-by-hand). |
| `association_builder` | `null`                          | A closure that builds the relations, see [Relations](ActiveRecord/Relations.md#has-many). |
| `connection`          | `primary`                       | The identifier of the connection.                                           |

A record extending another record must be added after its parent, see
[Extending another record](ActiveRecord/Relations.md#extending-another-record).

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Text;

class Session extends ActiveRecord
{
    #[Id, Character(64)]
    public string $id;

    #[Text]
    public string $data;
}

/* @var $builder \ICanBoogie\ActiveRecord\ConfigBuilder */

$config = $builder
    ->add_connection('cache', 'sqlite::memory:')
    ->use_attributes()
    ->add_record(Session::class, table_name: 'user_sessions', connection: 'cache')
    ->build();
```



## Building the config

`build()` validates the definitions and returns a `Config`, with the connection definitions in
`$config->connections`, and the model definitions in `$config->models`. An
[InvalidConfig][] exception is thrown when a definition is not valid, for instance when a record
uses a connection that is not defined, or extends a record that is not defined.

Building the config reads the attributes of the record classes, which takes some time. Since a
config can be exported with `var_export()`, it can be built once and cached:

```php
<?php

/* @var $config \ICanBoogie\ActiveRecord\Config */

$file = sys_get_temp_dir() . '/activerecord-config.php';

file_put_contents($file, '<?php return ' . var_export($config, true) . ';');

$config = require $file;
```



[Config]:        ../lib/ActiveRecord/Config.php
[ConfigBuilder]: ../lib/ActiveRecord/ConfigBuilder.php
[InvalidConfig]: ../lib/ActiveRecord/Config/InvalidConfig.php
