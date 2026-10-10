# Active records

An active record is an object-oriented representation of a row of a table. The columns are its
public properties, and its class usually implements getters, setters, and business logic. Record
classes extend [ActiveRecord][], and their schema is declared with attributes, see
[Schema](ActiveRecord/Schema.md).

The examples use the records of [Getting started](GettingStarted.md), and `$builder` is a
`ConfigBuilder` with these records already added.

The model of a record class is resolved with `model()`, which uses the
[static model provider](ActiveRecord/Model.md#the-static-model-provider). `query()` and `where()`
start a [query](ActiveRecord/Query.md) on that model.

```php
<?php

namespace App;

$model = Article::model();
$latest = Article::query()->order('-date')->take(10)->all;
$online = Article::where([ 'is_online' => true ])->all;
```



## Instantiating a record

Records are instantiated like any other object, but `from()` is often preferred for its shorter
notation:

```php
<?php

namespace App;

/* @var $alice User */

$article = Article::from([
    'user_id' => $alice->id,
    'title' => "An example",
    'body' => "Created with from().",
    'date' => '2026-10-12',
]);

echo $article->is_new ? "new" : "saved"; // new
```

`is_new` is `true` until the primary key is set, which happens when the record is saved.
`primary_key_value` is the value of the primary key, or an array of values for a primary key made of
multiple columns.



## Validating a record

`validate()` validates a record and returns a `ValidationErrors` instance, empty when the record is
valid. It is a prototype method, which the application binds, see
[Getting started](GettingStarted.md#binding-the-prototype-methods). The rules are provided by
`create_validation_rules()`, using the validators of [icanboogie/validate][], plus `unique`, which
checks that no other record has the same value.

<!-- doc-test: excerpt -->
```php
<?php

class User extends ActiveRecord
{
    // …

    public function create_validation_rules(): array
    {
        return [
            'username' => 'required|max-length:32|unique',
            'email' => 'required|email|unique',
        ];
    }
}
```

```php
<?php

namespace App;

$user = User::from([ 'username' => "alice", 'email' => "not an email" ]);
$errors = $user->validate();

echo count($errors); // 2, the username is already used and the email is not valid
```



## Saving a record

`save()` validates the record, then saves it, and returns the record. A [RecordNotValid][]
exception is thrown if the record is not valid, its `errors` property has the validation errors.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\RecordNotValid;

/* @var $article Article */

$article->title = "A new title";

try {
    $article->save();
} catch (RecordNotValid $e) {
    $errors = $e->errors;

    // …
}
```

Use `save(skip_validation: true)` to skip the validation.

A new record with a serial primary key is inserted, and its primary key is set. A record with a
primary key that is not serial, or made of multiple columns, is inserted or updated if it already
exists. When the record extends another record, its values are saved in the tables of the
hierarchy.

`alter_persistent_properties()` is invoked to alter the properties sent to the model. Override it
to add, remove, or alter properties without altering the record itself. By default, it removes the
`null` values of the columns that are not nullable.



## Deleting a record

`delete()` deletes the record from the database. A `LogicException` is thrown if the primary key of
the record is not set.

```php
<?php

namespace App;

/* @var $article Article */

$article->delete();
```



## Date time properties

The package comes with traits that implement date time properties, with property hooks. These
properties are always [DateTime][] instances, whatever the type of value used to set them: a
string, a `DateTimeInterface`, or `null`, which results in an empty date time.

| Trait                  | Property      |
|------------------------|---------------|
| `CreatedAtProperty`    | `created_at`  |
| `UpdatedAtProperty`    | `updated_at`  |
| `DateTimeProperty`     | `datetime`    |
| `DateProperty`         | `date`        |
| `StartAtProperty`      | `start_at`    |
| `StartedAtProperty`    | `started_at`  |
| `FinishAtProperty`     | `finish_at`   |
| `FinishedAtProperty`   | `finished_at` |

The traits are in the `ICanBoogie\ActiveRecord\Property` namespace. They don't declare a column,
add it with the schema builder:

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Property\StartAtProperty;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\SchemaBuilder;

class Event extends ActiveRecord
{
    use StartAtProperty;

    #[Id, Serial]
    public int $id;
}

/* @var $builder \ICanBoogie\ActiveRecord\ConfigBuilder */

$builder->add_record(Event::class, schema_builder: fn(SchemaBuilder $schema) => $schema->add_datetime('start_at'));

$event = new Event();

echo $event->start_at::class;    // ICanBoogie\DateTime
var_dump($event->start_at->is_empty); // bool(true)
$event->start_at = '2026-10-10 12:00:00';
echo $event->start_at;           // 2026-10-10T12:00:00Z
```



[ActiveRecord]:         ../lib/ActiveRecord.php
[DateTime]:             https://github.com/ICanBoogie/DateTime
[RecordNotValid]:       ../lib/ActiveRecord/RecordNotValid.php
[icanboogie/validate]:  https://github.com/ICanBoogie/Validate
