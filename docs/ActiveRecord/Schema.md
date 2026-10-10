# Schema

The schema of a record defines the columns of its table, its primary key, and its indexes. It is
declared with attributes on the record class, or built with a [SchemaBuilder][].

The examples define a `Product` record, and `$builder` is a `ConfigBuilder` with the records of
[Getting started](../GettingStarted.md).



## Declaring the schema with attributes

Call `ConfigBuilder::use_attributes()` to build schemas from the attributes of the record classes.
A column attribute goes on a public property, which takes the name of the column.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Decimal;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Index;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\Serial;

#[Index('sku', unique: true)]
class Product extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[Character(16, fixed: true)]
    public string $sku;

    #[Character(80)]
    public string $name;

    #[Decimal(10, scale: 2)]
    public string $price;

    #[Integer(Integer::SIZE_SMALL, unsigned: true, default: 0)]
    public int $stock;

    #[Boolean(default: true)]
    public bool $is_available;

    #[DateTime(default: DateTime::CURRENT_TIMESTAMP)]
    public string $created_at;
}

$config = new ConfigBuilder()
    ->use_attributes()
    ->add_connection('primary', 'sqlite::memory:')
    ->add_record(Product::class)
    ->build();
```

An exception is thrown if attributes are enabled, a record defines no column, and no
`schema_builder` is given.



## Building the schema by hand

The `schema_builder` parameter of `ConfigBuilder::add_record()` builds the schema with a
[SchemaBuilder][]. If attributes are enabled, the builder starts with the columns declared by the
attributes, and a column added with the same name replaces it.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Schema\DateTime;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\SchemaBuilder;

class Product extends ActiveRecord
{
}

/* @var $builder \ICanBoogie\ActiveRecord\ConfigBuilder */

$builder->add_record(
    record_class: Product::class,
    schema_builder: fn(SchemaBuilder $schema) => $schema
        ->add_serial('id', primary: true)
        ->add_character('sku', 16, fixed: true)
        ->add_character('name', 80)
        ->add_decimal('price', 10, scale: 2)
        ->add_integer('stock', Integer::SIZE_SMALL, unsigned: true, default: 0)
        ->add_boolean('is_available', default: true)
        ->add_datetime('created_at', default: DateTime::CURRENT_TIMESTAMP)
        ->add_index('sku', unique: true),
);
```



## Columns

| Attribute       | Builder method                     | Notes                                                                  |
|-----------------|------------------------------------|------------------------------------------------------------------------|
| [Integer][]     | `add_integer()`, `add_foreign()`   | `size` is one of `Integer::SIZE_*`, from `SIZE_TINY` to `SIZE_BIG`.    |
| [Serial][]      | `add_serial()`                     | An auto-incremented, unsigned, unique integer.                         |
| [BelongsTo][]   | `belongs_to()`                     | An integer referencing another record, see [Relations](Relations.md).  |
| [Boolean][]     | `add_boolean()`                    | Not an `Integer`, `instanceof Integer` doesn't match it.               |
| [Decimal][]     | `add_decimal()`, `add_float()`     | `precision` and `scale`. `approximate: true` makes it a float.         |
| [Character][]   | `add_character()`                  | `size` defaults to 255. `fixed: true` for `CHAR` instead of `VARCHAR`. |
| [Text][]        | `add_text()`                       | `size` is one of `Text::SIZE_*`.                                       |
| [Binary][]      | `add_binary()`                     | `size` defaults to 255. `fixed: true` for `BINARY`.                    |
| [Blob][]        | `add_blob()`                       | `size` is one of `Blob::SIZE_*`.                                       |
| [DateTime][]    | `add_datetime()`                   |                                                                        |
| [Timestamp][]   | `add_timestamp()`                  |                                                                        |
| [Date][]        | `add_date()`                       |                                                                        |
| [Time][]        |                                    |                                                                        |

All the columns take `null` to make them nullable, and `unique` to add a unique constraint.
Character and text columns take a `collate`.

The types are rendered for each engine, and some aren't available everywhere, see
[Database engines](../Engines.md#schema).



### Default values

A default value is a plain value, not SQL: `#[Character(default: "madonna")]` renders
`DEFAULT 'madonna'`, quoted and escaped. The following values are rendered as is:

- Numbers, on numeric columns.
- `true` and `false` on boolean columns, rendered as `TRUE` and `FALSE`.
- The `CURRENT_TIMESTAMP`, `CURRENT_DATE`, and `CURRENT_TIME` keywords, available as constants of
  the date and time attributes, e.g. `DateTime::CURRENT_TIMESTAMP`.



## Primary key

The [Id][] attribute marks the properties making the primary key. Use it on several properties for
a primary key made of multiple columns. With the builder, pass `primary: true` to
`add_serial()`, `add_integer()`, `add_foreign()`, or `add_character()`.

A record that extends another record shares the primary key of its parent, see
[Relations](Relations.md#extending-another-record).



## Indexes

The [Index][] attribute, repeatable on the record class, adds an index on one or more columns. With
the builder, use `add_index()`. An index can be unique, and can be named, for instance
`#[Index([ 'name', 'price' ], name: 'idx_name_price')]`.



## Custom columns

A custom column extends one of the column attributes and implements [ResolvesToColumn][], to
resolve into a basic column when the table is created. The following example defines a `Uuid`
column, rendered as a fixed character column:

```php
<?php

namespace App\Schema;

use Attribute;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Column;
use ICanBoogie\ActiveRecord\Schema\ResolvesToColumn;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Uuid extends Character implements ResolvesToColumn
{
    public function __construct(bool $null = false, bool $unique = false)
    {
        parent::__construct(size: 36, fixed: true, null: $null, unique: $unique);
    }

    public function resolve(): Column
    {
        return new Character(size: $this->size, fixed: true, null: $this->null, unique: $this->unique);
    }
}
```



[BelongsTo]:        ../../lib/ActiveRecord/Schema/BelongsTo.php
[Binary]:           ../../lib/ActiveRecord/Schema/Binary.php
[Blob]:             ../../lib/ActiveRecord/Schema/Blob.php
[Boolean]:          ../../lib/ActiveRecord/Schema/Boolean.php
[Character]:        ../../lib/ActiveRecord/Schema/Character.php
[Date]:             ../../lib/ActiveRecord/Schema/Date.php
[DateTime]:         ../../lib/ActiveRecord/Schema/DateTime.php
[Decimal]:          ../../lib/ActiveRecord/Schema/Decimal.php
[Id]:               ../../lib/ActiveRecord/Schema/Id.php
[Index]:            ../../lib/ActiveRecord/Schema/Index.php
[Integer]:          ../../lib/ActiveRecord/Schema/Integer.php
[ResolvesToColumn]: ../../lib/ActiveRecord/Schema/ResolvesToColumn.php
[SchemaBuilder]:    ../../lib/ActiveRecord/SchemaBuilder.php
[Serial]:           ../../lib/ActiveRecord/Schema/Serial.php
[Text]:             ../../lib/ActiveRecord/Schema/Text.php
[Time]:             ../../lib/ActiveRecord/Schema/Time.php
[Timestamp]:        ../../lib/ActiveRecord/Schema/Timestamp.php
