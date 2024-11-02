# Active Record

[![Release](https://img.shields.io/packagist/v/ICanBoogie/activerecord.svg)](https://packagist.org/packages/icanboogie/activerecord)
[![Code Quality](https://img.shields.io/scrutinizer/g/ICanBoogie/ActiveRecord.svg)](https://scrutinizer-ci.com/g/ICanBoogie/ActiveRecord)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/ActiveRecord.svg)](https://coveralls.io/r/ICanBoogie/ActiveRecord)
[![Downloads](https://img.shields.io/packagist/dt/icanboogie/activerecord.svg)](https://packagist.org/packages/icanboogie/activerecord)

__Connections__, __models__ and __active records__ are the foundations of everything that concerns
database access and management. They are used to establish database connections, manage tables and
their possible relationship, as well as manage the records of these tables. Leveraging OOP, the
models and active records are instances which properties, getters/setters and behavior can be
inherited in a business logic.

Using the __query interface__, you won't have to write raw SQL, manage table relationship,
or worry about injection.

Finally, using __providers__ you can define all your connections and models in a single place.
Connections are established and models are instantiated on demand, so feel free the define
hundreds of them.



#### Installation

```shell
composer require icanboogie/activerecord
```



### Acknowledgments

The implementation of the query interface is vastly inspired by
[Ruby On Rails' Active Record Query Interface](http://guides.rubyonrails.org/active_record_querying.html).





## Getting started

Unless you bound **ActiveRecord** to [ICanBoogie][] using the [icanboogie/bind-activerecord][]
package, you need to bind the prototype methods `Model::lazy_get_activerecord_cache` and
 `ActiveRecord::validate`.

The following code should do the trick:

```php
<?php

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Validate\ValidateActiveRecord;
use ICanBoogie\ActiveRecord\ActiveRecordCache\RuntimeActiveRecordCache;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Prototype;

Prototype::configure([

    ActiveRecord::class => [

        'validate' => function(ActiveRecord $record) {

            static $validate;

            $validate ??= new ValidateActiveRecord;

            return $validate($record);

        }

    ],

    Model::class => [

        'lazy_get_activerecord_cache' => fn(Model $model) =>
            new RuntimeActiveRecordCache($model),

    ]

]);
```





## Establishing a connection to a database

A connection to a database is created with a [Connection][] instance.

The following code demonstrates how a connection can be established to a MySQL database and a
SQLite temporary database:

```php
<?php

use ICanBoogie\ActiveRecord\Connection;

# a connection to a MySQL database
$connection = new Connection('mysql:dbname=example', 'username', 'password');

# a connection to a SQLite temporary database stored in memory
$connection = new Connection('sqlite::memory:');
```

The [Connection][] class extends [PDO][]. It takes the same parameters, and custom options can
be provided with the driver options to specify a prefix to table names, specify the charset and
collate of the connection or its timezone.





### Defining the prefix of the database tables

`ConnectionAttributes::$table_name_prefix` specifies the prefix for all the tables name
of the connection. Thus, if the `icybee` prefix is defined the `nodes` table is
renamed as `icybee_nodes`.

The `{table_name_prefix}` placeholder is replaced in queries by the prefix:

```php
<?php

/* @var $connection \ICanBoogie\ActiveRecord\Connection */

$statement = $connection('SELECT * FROM `{table_name_prefix}nodes` LIMIT 10');
```





### Defining the charset and collate to use

`ConnectionAttributes::$charset_and_collate` specifies the charset and collate of the connection
in a single string e.g. "utf8/general_ci" for the "utf8" charset and the "utf8_general_ci" collate.

The `{charset}` and `{collate}` placeholders are replaced in queries:

```php
<?php

/* @var $connection \ICanBoogie\ActiveRecord\Connection */

$connection('ALTER TABLE nodes CHARACTER SET "{charset}" COLLATE "{collate}"');
```




### Specifying a time zone

`ConnectionAttributes::$time_zone` specifies the time zone of the connection.





## Model overview

A _model_ is an object-oriented representation of a database table, or a group of tables.
A model is used to create, update, delete and query records. Models are instances of the [Model][]
class, and usually implement a specific business logic.

```php
<?php

namespace App\Modules\Nodes;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ConnectionCollection;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;

/**
 * @extends Model<int, Node>
 */
 #[Model\Record(Node:::class)]
class NodeModel extends Model
{
}

/**
 * @extends ActiveRecord<int>
 */
class Node extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[Character(80)]
    public string $title;

    #[Integer]
    public int $number;

    // …
}

$config = (new ActiveRecord\ConfigBuilder())
    ->use_attributes()
    ->add_connection(/*...*/)
    ->add_record(NodeModel::class)
    ->build();

/* @var $connections ConnectionCollection */

$models = new ModelCollection($connections, $config->models);
$models->install();

$node_model = $models->model_for_record(Node::class);

$node = new Node($node_model);
//               ^^^^^^^^^^^
// because we don't use a model provider yet, we need to specify the model to the active record

$node->title = "My first node";
$node->number = 123;
$id = $node->save();
# or
$id = $node->id;

echo "Saved node, got id: $id\n";
```





### Defining the name of the table

The `$name` argument specifies the name of the table. If a table prefix is defined by the
connection, it is used to prefix the table name. The `name` and `unprefixed_name` properties returns
the prefixed name and original name of the table:

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

echo "table name: {$model->name}, original: {$model->unprefixed_name}.";
```

The `{self}` placeholder is replaced in queries by the `name` property:

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

$stmt = $model('SELECT * FROM `{self}` LIMIT 10');
```





### Defining the schema of the model

To specify the schema, tt is recommended to use attributes on your ActiveRecord class:

- [Boolean](lib/ActiveRecord/Schema/Boolean.php)
- [Integer](lib/ActiveRecord/Schema/Integer.php)
- [Serial](lib/ActiveRecord/Schema/Serial.php)
- [BelongsTo](lib/ActiveRecord/Schema/BelongsTo.php)
- [Decimal](lib/ActiveRecord/Schema/Decimal.php)
- [Character](lib/ActiveRecord/Schema/Character.php)
- [Text](lib/ActiveRecord/Schema/Text.php)
- [Binary](lib/ActiveRecord/Schema/Binary.php)
- [Blob](lib/ActiveRecord/Schema/Blob.php)
- [DateTime](lib/ActiveRecord/Schema/DateTime.php)
- [Timestamp](lib/ActiveRecord/Schema/Timestamp.php)
- [Date](lib/ActiveRecord/Schema/Date.php)
- [Time](lib/ActiveRecord/Schema/Time.php)
- [Id](lib/ActiveRecord/Schema/Id.php)
- [HasMany](lib/ActiveRecord/Schema/HasMany.php)

Alternatively, you can use `$schema_builder` to build the schema by hand:

```php
<?php

/* @var \ICanBoogie\ActiveRecord\SchemaBuilder $schema */

$schema
    ->add_serial('id', primary: true)
    ->add_character('title', 80)
    ->add_integer('number')
```



### Creating the table associated with a model

Once the model has been defined, its associated table can easily be created with the `install()`
method.

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

$model->install();
```

The `is_installed()` method checks if a model has already been installed.

Note: The method only checks if the corresponding table exists, not if its schema is correct.

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

if ($model->is_installed()) {
    echo "The model is already installed.";
}
```




### Placeholders

The following placeholders are replaced in model queries:

* `{alias}`: The alias of the table.
* `{prefix}`: The prefix of the table names of the connection.
* `{primary}`: The primary key of the table.
* `{self}`: The name of the table.
* `{self_and_related}`: The escaped name of the table and the possible JOIN clauses.





## Defining the relations between models

### Extending another model

A model can extend another, just like a class can extend another in PHP. Fields are inherited
and the primary key of the parent is used to link records together. When the model is queried, the
tables are joined. When values are inserted or updated, they are split to update the various
tables. Also, the connection of the parent model is inherited.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\SchemaBuilder;
use ICanBoogie\DateTime;

/* @var ConfigBuilder $config */

$config
    ->add_record(
        record_class: Node::class,
        schema_builder: fn(SchemaBuilder $b) => $b
            ->add_serial('nid', primary: true)
            ->add_character('title'),
    )
    ->add_record(
        record_class: Article::class,
        schema_builder: fn(SchemaBuilder $b) => $b
            ->add_character('body')
            ->add_date('date'),
    );

// …

$article = Article::from([
    'title' => "My Article",
    'body' => "Testing",
    'date' => DateTime::now()
]);

$article->save();
```

Contrary to tables, models are not required to define a schema if they extend another model, but
they may end with different parents.

In the following example the parent _table_ of `news` is `nodes` but its parent _model_ is
`contents`. That's because `news` doesn't define a schema and thus inherits the schema and some
properties of its parent model.

```php
<?php

/* @var $news \ICanBoogie\ActiveRecord\Model */

echo $news->parent::class;       // NodeModel
echo $news->parent_model::class; // ContentModel
```





### One-to-one relation (belongs_to)

Records of a model can belong to records of other models. For instance, a news article belonging
to a user. The relation is specified with the `BELONGS_TO` attribute. When the _belongs to_
relation is specified, a getter is automatically added to the prototype of the records. For
instance, if records of a `news` model belong to records of a `users` model, than the `get_user`
getter is added to the prototype of the records of the `news` model. The user of a news record
can then by obtained using the magic property `user`.

```php
<?php

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\SchemaBuilder;

class Article extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(User::class)]
    public int $uid;
}

class User extends ActiveRecord
{
    #[Id, Serial]
    public int $uid;

    #[Character]
    public string $name;
}

// …

/* @var $news Model */

$record = $news->query()->one;

echo "{$record->title} belongs to {$record->user->name}.";
```





### One-to-many relation (has_many)

A one-to-many relation can be established between two models. For instance, an article having
many comments. The relation is specified with the [HasMany](lib/ActiveRecord/Schema/HasMany.php)
attribute. A getter is added to the active record class of the model and returns a [Query][]
instance when it is accessed.

The following example demonstrates how a one-to-many relation can be established between the
"articles" and "comments" models, while creating the models:

```php
<?php

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\ModelCollection;
use ICanBoogie\ActiveRecord\SchemaBuilder;

class Article extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(User::class)]
    public int $uid;
}

#[HasMany(Article::class)]
class User extends ActiveRecord
{
    #[Id, Serial]
    public int $uid;

    #[Character]
    public string $name;
}

// …

/* @var $user User */

foreach ($user->articles as $article) {
    echo "User {$user->name} has article {$article->id}.";
}

```



## Active Records

An active record is an object-oriented representation of a record in a database. Usually, the
table columns are its public properties, and it is not unusual that getters/setters and business
logic methods are implemented by its class.

If the model managing the record is not specified when the instance is created, [StaticModelResolver](lib/ActiveRecord/StaticModelResolver.php) will be used to resolve the model when needed.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;

class Node extends ActiveRecord
{
    // …

    protected function get_next()
    {
        return $this->model->own->visible->where('date > ?', $this->date)->order('date')->one;
    }

    protected function get_previous()
    {
        return $this->model->own->visible->where('date < ?', $this->date)->order('date DESC')->one;
    }

    // …
}
```





### Instantiating an active record

Active record are instantiated just like any other object, but the `from()` method is
usually preferred for its shorter notation:

```php
<?php

$record = Article::from([

    'title' => "An example",
    'body' => "My first article",
    'language' => "en",
    'is_online' => true

]);
```





### Validating an active record

The `validate()` method validates an active record and returns a [ValidationErrors][] instance on failure or an empty array on success. Your active record class should implement the `create_validation_rules()` method to provide validation rules.

For following example demonstrates how a `User` active record class could implement the `create_validation_rules()` method to validate its properties.

```php
<?php

use ICanBoogie\ActiveRecord;

// …

class User extends ActiveRecord
{
    use ActiveRecord\Property\CreatedAtProperty;
    use ActiveRecord\Property\UpdatedAtProperty;

    #[Id, Serial]
    public int $id;
    #[Character]
    public string $username;
    #[Character(unique: true)]
    public string $email;

    // …

    /**
     * @inheritdoc
     */
    public function create_validation_rules()
    {
        return [

            'username' => 'required|max-length:32|unique',
            'email' => 'required|email|unique',
            'created_at' => 'required|datetime',
            'updated_at' => 'required|datetime',

        ];
    }
}

// ...

$user = new User;
$errors = $user->validate();

if ($errors)
{
    // …
}
```





### Saving an active record

Most properties of an active record are persistent. The `save()` method is used to save the active
record to the database.

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

$record = $model->find(10);
$record->is_online = false;
$record->save();
```

Before a record is saved it is validated with the `validate()` method and if the validation fails a [RecordNotValid][] exception is thrown with the validation errors.

```php
<?php

use ICanBoogie\ActiveRecord\RecordNotValid;

try
{
    $record->save();
}
catch (RecordNotValid $e)
{
    $errors = $e->errors;

    // …
}
```

The validation may be skipped using the `SAVE_SKIP_VALIDATION` option, of course the outcome is then unpredictable so use this option carefully:

```php
<?php

use ICanBoogie\ActiveRecord;

$record->save([ ActiveRecord::SAVE_SKIP_VALIDATION => true ]);
```

The `alter_persistent_properties()` is invoked to alter the properties
that will be sent to the model. One may extend the method to add, remove or alter properties
without altering the instance itself.





### Deleting an active record

The `delete()` method deletes the active record from the database:

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

$record = $model->find(190);
$record->delete();
```





### Date time properties

The package comes with three trait properties especially designed to handle [DateTime][]
instances: [DateTimeProperty][], [CreatedAtProperty][], [UpdatedAtProperty][]. Using this
properties you are guaranteed to always get a [DateTime][] instance, no matter what value type
is used to set the date and time.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Property\CreatedAtProperty;
use ICanBoogie\ActiveRecord\Property\UpdatedAtProperty;

class Node extends ActiveRecord
{
    public $title;

    use CreatedAtProperty;
    use UpdatedAtProperty;
}

$node = new Node;

echo get_class($node->created_at);   // ICanBoogie\Datetime
echo $node->created_at->is_empty;    // true
$node->created_at = 'now';
echo $node->created_at;              // 2014-02-21T15:00:00+0100
```





### Retrieving records

Use a `find()` method to retrieve one or multiple records.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$article = $model->find(10);
```

Retrieving a set or records using their primary key is really simple too:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$articles = $model->find(10, 32, 89);
```

The [RecordNotFound][] exception is thrown when a record could not be found. Its `records` property
can be used to know which records could be found and which could not.

Note: The records of the set are returned in the same order they are requested, this also applies
to the `records` property of the [RecordNotFound][] exception.





### The Query Interface

Queries often start from a model, with `$model->query()` or `$model->where()`.

See [The Query Interface](docs/ActiveRecord/Query.md)





### Providing your own query

By default, the query object is [Query][] instance, but the class of the query can be specified with
the `QUERY_CLASS` attribute. This is often used to add features to the query.





## Providers

Providers are included to manage connections and models.





### The connection provider

The connections provider manages database connections.





#### Defining connections

Connection definitions can be specified while creating the [ConnectionCollection][] instance.
[ConnectionCollection][] implements [ConnectionProvider][], it is recommended to type against the
interface and use the method `connection_for_id()`.

```php
<?php

use ICanBoogie\ActiveRecord\Config\ConnectionDefinition;
use ICanBoogie\ActiveRecord\ConnectionCollection;

$connections = new ConnectionCollection([

    new ConnectionDefinition(id: 'read', dsn: 'sqlite::memory:'),
    new ConnectionDefinition(id: 'write', dsn: 'mysql:dbname=my_database'),

]);

$connection = $connections->connection_for_id('read');
```

Database connections are created on demand, you can define a hundred of them, but they
are only established when needed.

A [ConnectionNotDefined][] exception is thrown in attempt to obtain a connection
that is not defined.





#### Checking defined connections

```php
<?php

/* @var $connections \ICanBoogie\ActiveRecord\ConnectionCollection */

if (isset($connections->definitions['one']))
{
    echo "The connection 'one' is defined.\n";
}
```





#### Established connections

An array with the established connections can be retrieved using the `established` magic property.
The property is _read-only_.

```php
<?php

/* @var $connections \ICanBoogie\ActiveRecord\ConnectionCollection */

foreach ($connections->established as $id => $connection)
{
    echo "The connection '$id' is established.\n";
}
```

The [ConnectionCollection][] instance itself can be used to traverse established connections.

```php
<?php

/* @var $connections \ICanBoogie\ActiveRecord\ConnectionCollection */

foreach ($connections as $id => $connection)
{
    echo "The connection '$id' is established.\n";
}
```





### Model collection

Models are managed using a model collection that resolves model attributes (such as database
connections) and instantiate them.





#### Defining models

Model definitions can be specified while creating the [ModelCollection][] instance.
[ModelCollection][] implements [ModelProvider][], it is recommended to type against the interface
and use the method `model_for_id()` to retrieve models from the collection.

Note: You don't have to create the [Connection][] instances used by the models, you can use their
identifier which will get resolved when the model is needed.

Note: If `CONNECTION` is not specified the `primary` connection is used.

```php
<?php

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;

/* @var $connections \ICanBoogie\ActiveRecord\ConnectionCollection */

$models = new ModelCollection($connections, [

    'nodes' => [

        // …
        Model::SCHEMA => [

            'nid' => 'serial',
            'title' => 'varchar'
            // …
        ]
    ],

    'contents' => [

        // …
        Model::EXTENDING => 'nodes'
    ]
]);

$model = $models->model_for_id('nodes');
```

Model definitions can be modified or added after the [ModelCollection][] instance has been created.

```php
<?php

use ICanBoogie\ActiveRecord\Model;

/* @var $models \ICanBoogie\ActiveRecord\ModelCollection */

$models['new'] = [

    // …
    Model::EXTENDING => 'contents'
];
```

You can modify the definition of a model until it is instantiated. A [ModelAlreadyInstantiated][]
exception is thrown in attempt to modify the definition of an already instantiated model.





#### Obtaining a model

Use the [ModelCollection][] instance as an array to obtain a [Model][] instance.

```php
<?php

/* @var $models \ICanBoogie\ActiveRecord\ModelCollection */

$nodes = $models['nodes'];
```

Models are instantiated on demand, so that you can define a hundred models an they will only by
instantiated, along with their database connection, when needed.

A [ModelNotDefined][] exception is thrown in attempts to obtain a model which is not defined.





#### Instantiated models

An array with the instantiated models can be retrieved using the `instances` magic property. The
property is _read-only_.

```php
<?php

/* @var $models \ICanBoogie\ActiveRecord\ModelCollection */

foreach ($models->instances as $class => $model)
{
    echo "The model '$class' has been instantiated.\n";
}
```





#### Installing / Uninstalling models

All the models managed by the provider can be installed and uninstalled with a single command
using the `install()` and `uninstall()` methods. The `is_installed()` method returns an array
of key/value pair where _key_ is a model identifier and _value_ `true` if the model is
installed, `false` otherwise.

```php
<?php

/* @var $models \ICanBoogie\ActiveRecord\ModelCollection */

$models->install();
var_dump($models->is_installed()); // [ "NodeModel" => true, "ContentModel" => true ]
$models->uninstall();
var_dump($models->is_installed()); // [ "NodeModel" => false, "ContentModel" => false ]
```





#### Model provider

`StaticModelProvider::model_for_record()` is used by active records to retrieve their model when
required, and by queries during joins. Models are retrieved using the model collection returned by
[ModelProvider][].

The following example demonstrates how to define a factory for a model provider:

```php
<?php

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\StaticModelProvider;

/* @var $provider ActiveRecord\ModelProvider */

StaticModelProvider::set(fn() => $provider);

$nodes = StaticModelProvider::model_for_record(Node::class);
```

> **Note:** The factory is invoked once.





## Records caching

By default, each model uses an instance of [RuntimeActiveRecordCache][] to cache its records.
This cache stores the records for the duration of the request, it is brand new with each HTTP
request. The cache is obtained using the prototype features of the model, through the
`activerecord_cache` magic property.

Third parties can provide a different cache instance simply by overriding the
`lazy_get_activerecord_cache` method:

```php
<?php

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Prototype;

Prototype::from('ICanBoogie\ActiveRecord\Model')['lazy_get_activerecord_cache'] = function(Model $model) {

    return new MyActiveRecordCache($model);

};
```

Or using a `prototype` configuration fragment:

```php
<?php

// config/prototype.php

return [

    'ICanBoogie\ActiveRecord\Model::lazy_get_activerecord_cache' => 'my_activerecord_cache_provider'

];
```





## Exceptions

The exception classes defined by the package implement the `ICanBoogie\ActiveRecord\Exception`
interface so that they can easily be identified:

```php
<?php

try
{
    // …
}
catch (\ICanBoogie\ActiveRecord\Exception $e)
{
    // an ActiveRecord exception
}
catch (\Exception $e)
{
    // some other exception
}
```

The following exceptions are defined:

- [ConnectionAlreadyEstablished][]: Exception thrown in attempt to set/unset the definition of an
already established connection.
- [ConnectionNotDefined][]: Exception thrown in attempt to obtain a connection that is not defined.
- [ConnectionNotEstablished][]: Exception thrown when a connection cannot be established.
- [ModelAlreadyInstantiated][]: Exception thrown in attempt to set/unset the definition of an
already instantiated model.
- [ModelNotDefined][]: Exception thrown in attempt to obtain a model that is not defined.
- [RecordNotFound][]: Exception thrown when one or several records cannot be found.
- [RelationNotDefined][]: Exception thrown in attempt to obtain a relation that is not defined.
- [ScopeNotDefined][]: Exception thrown in attempt to obtain a scope that is not defined.
- [StatementInvocationFailed][]: Exception thrown when invoking a statement fails (`execute()` returned `false`).
- [StatementNotValid][]: Exception thrown in attempt to execute a statement that is not valid.
- [UnableToSetFetchMode][]: Exception thrown when the fetch mode of a statement fails to be set.





----------



## Continuous Integration

The project is continuously tested by [GitHub actions](https://github.com/ICanBoogie/ActiveRecord/actions).

[![Tests](https://github.com/ICanBoogie/ActiveRecord/workflows/test/badge.svg)](https://github.com/ICanBoogie/ActiveRecord/actions?query=workflow%3Atest)
[![Static Analysis](https://github.com/ICanBoogie/ActiveRecord/workflows/static-analysis/badge.svg)](https://github.com/ICanBoogie/ActiveRecord/actions?query=workflow%3Astatic-analysis)
[![Code Style](https://github.com/ICanBoogie/ActiveRecord/workflows/code-style/badge.svg)](https://github.com/ICanBoogie/ActiveRecord/actions?query=workflow%3Acode-style)



## Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in
this project and its community, you are expected to uphold this code.



## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.



## License

**icanboogie/activerecord** is released under the [BSD3-Clause](LICENSE).



[Connection]:                   https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.Connection.html
[ConnectionAlreadyEstablished]: https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ConnectionAlreadyEstablished.html
[ConnectionNotDefined]:         https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ConnectionNotDefined.html
[ConnectionNotEstablished]:     https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ConnectionNotEstablished.html
[ConnectionCollection]:         https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ConnectionCollection.html
[CreatedAtProperty]:            https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.Property.CreatedAtProperty.html
[DateTimeProperty]:             https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.Property.DateTimeProperty.html
[Model]:                        https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.Model.html
[ModelAlreadyInstantiated]:     https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ModelAlreadyInstantiated.html
[ModelNotDefined]:              https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ModelNotDefined.html
[ModelCollection]:              https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ModelCollection.html
[ModelProvider]:                https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ModelProvider.html
[Query]:                        https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.Query.html
[RecordNotFound]:               https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.RecordNotFound.html
[RecordNotValid]:               https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.RecordNotValid.html
[RelationNotDefined]:           https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.RelationNotDefined.html
[RuntimeActiveRecordCache]:     https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ActiveRecordCache.RuntimeActiveRecordCache.html
[ScopeNotDefined]:              https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.ScopeNotDefined.html
[StatementInvocationFailed]:    https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.StatementInvocationFailed.html
[StatementNotValid]:            https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.StatementNotValid.html
[UnableToSetFetchMode]:         https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.UnableToSetFetchMode.html
[UpdatedAtProperty]:            https://icanboogie.org/api/activerecord/master/class-ICanBoogie.ActiveRecord.Property.UpdatedAtProperty.html
[DateTime]:                     https://icanboogie.org/api/datetime/master/class-ICanBoogie.DateTime.html
[ValidationErrors]:             https://icanboogie.org/api/validate/master/class-ICanBoogie.Validate.ValidationErrors.html
[icanboogie/bind-activerecord]: https://github.com/ICanBoogie/bind-activerecord
[ICanBoogie]:                   https://icanboogie.org
[PDO]:                          http://php.net/manual/en/book.pdo.php
[ConnectionProvider]:           lib/ActiveRecord/ConnectionProvider.php
[ModelProvider]:                lib/ActiveRecord/ModelProvider.php
