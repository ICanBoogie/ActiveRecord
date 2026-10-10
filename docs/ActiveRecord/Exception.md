# Exceptions

The exceptions defined by the package implement the [Exception][] interface, so they can be
identified easily:

```php
<?php

try {
    // …
} catch (\ICanBoogie\ActiveRecord\Exception $e) {
    // an ActiveRecord exception
} catch (\Exception $e) {
    // some other exception
}
```

| Exception                      | Thrown when                                                                                  |
|--------------------------------|----------------------------------------------------------------------------------------------|
| [Config\InvalidConfig][]       | The config built with `ConfigBuilder` is not valid, e.g. a relation refers to an undefined record. |
| [ConnectionNotDefined][]       | A connection is requested with an identifier that is not defined.                            |
| [ConnectionNotEstablished][]   | A connection can't be established. The message doesn't include the DSN or the credentials.   |
| [DriverNotDefined][]           | The DSN uses a driver other than `sqlite`, `mysql`, or `pgsql`.                              |
| [RecordNotFound][]             | `Model::find()` can't find one or more records. `records` has the records that were found.   |
| [RecordNotValid][]             | A record is not valid when it is saved. `errors` has the validation errors.                  |
| [RelationNotDefined][]         | A relation is requested from `Model::$relations` with a name that is not defined.            |
| [StatementNotValid][]          | A statement can't be prepared or executed. `statement`, `args`, and `original`, the `PDOException`, describe it. |
| [UnableToSetFetchMode][]       | The fetch mode of a statement can't be set.                                                  |

Some errors are reported with SPL exceptions instead, such as the `LogicException` thrown when a
model is requested for a record class that is not defined, or when a record without a primary key
is deleted.



[Exception]:                ../../lib/ActiveRecord/Exception.php
[Config\InvalidConfig]:     ../../lib/ActiveRecord/Config/InvalidConfig.php
[ConnectionNotDefined]:     ../../lib/ActiveRecord/ConnectionNotDefined.php
[ConnectionNotEstablished]: ../../lib/ActiveRecord/ConnectionNotEstablished.php
[DriverNotDefined]:         ../../lib/ActiveRecord/DriverNotDefined.php
[RecordNotFound]:           ../../lib/ActiveRecord/RecordNotFound.php
[RecordNotValid]:           ../../lib/ActiveRecord/RecordNotValid.php
[RelationNotDefined]:       ../../lib/ActiveRecord/RelationNotDefined.php
[StatementNotValid]:        ../../lib/ActiveRecord/StatementNotValid.php
[UnableToSetFetchMode]:     ../../lib/ActiveRecord/UnableToSetFetchMode.php
