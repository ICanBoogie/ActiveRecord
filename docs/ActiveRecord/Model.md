# Models

A [Model][] manages the table of a record class: it finds, queries, saves, and deletes records. It
extends [Table][], which holds the table name, the schema, and the connection. Models are created
by a [ModelRegistry][] from the definitions built with `ConfigBuilder`, there's one model per
record class.

The examples use the records of [Getting started](../GettingStarted.md). `$model` is the model of
`Article`, and `$builder` is a `ConfigBuilder` with the records already added.



## Obtaining a model

From a record class, with `model()`. It uses [StaticModelProvider](#the-static-model-provider).

```php
<?php

namespace App;

$articles = Article::model();
```

From a [ModelProvider][], such as a [ModelRegistry][]:

```php
<?php

namespace App;

/* @var $models \ICanBoogie\ActiveRecord\ModelProvider */

$articles = $models->model_for_record(Article::class);
```



## Table name and alias

The table name is the plural of the record class name, in snake case: `Article` is stored in
`articles`, and `BlogPost` in `blog_posts`. The alias, used in queries, is the singular of the table
name: `article`. Both can be specified when the record is added:

```php
<?php

namespace App;

/* @var $builder \ICanBoogie\ActiveRecord\ConfigBuilder */

$builder->add_record(Article::class, table_name: 'posts', alias: 'post');
```

A model provides the following properties:

- `name`: The table name, with the prefix of the connection.
- `unprefixed_name`: The table name, without the prefix.
- `alias`: The alias of the table.
- `primary`: The primary key, a column name or an array of column names.
- `schema`: The [Schema][] of the table.
- `extended_schema`: The schema including the columns of the parent tables.
- `parent`: The model of the parent record, see [Relations](Relations.md#extending-another-record).
- `connection`: The [Connection][] used by the model.
- `activerecord_class`: The record class.
- `relations`: The [relations](Relations.md) of the model.



## Running statements

A model can be invoked with a statement and its arguments, and returns a [Statement][]. The
following placeholders are replaced in the statement:

- `{self}`: The table name.
- `{alias}`: The alias of the table.
- `{primary}`: The primary key of the table.
- `{prefix}`: The table name prefix of the connection.
- `{self_and_related}`: The table name and the joins of the parent tables.

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

$bodies = $model('SELECT body FROM {self} WHERE {primary} > ?', [ 1 ])->all;
$titles = $model('SELECT title FROM {self_and_related} WHERE views > ?', [ 10 ])->all;
```

The query interface covers most needs without writing SQL, see below.



## Finding records

`find()` retrieves a record, or several records, with their primary key:

```php
<?php

/* @var $model \ICanBoogie\ActiveRecord\Model */

$article = $model->find(1);
$articles = $model->find(2, 1); // [ 2 => Article, 1 => Article ]
```

The records are returned in the requested order, keyed by primary key. A [RecordNotFound][]
exception is thrown when a record can't be found. Its `records` property has the records that were
found, and `null` for the ones that weren't.



## Querying records

`query()` returns a new [Query][], and `where()` a query with conditions. The same methods are
available as static methods of the record class. See [The query interface](Query.md).

```php
<?php

namespace App;

/* @var $model \ICanBoogie\ActiveRecord\Model */

$articles = $model->where([ 'is_online' => true ])->order('-date')->all;
$articles = Article::where([ 'is_online' => true ])->order('-date')->all;
$count = Article::query()->count;
```



## Writing rows

Records are usually saved and deleted through the [active records](../ActiveRecord.md), but a model
also writes rows directly:

- `save(array $values, ?int $id = null)`: Inserts a row, or updates the row matching `$id`, and
  returns its primary key. When the table has a parent, the values are spread over the tables.
- `insert(array $values, bool $ignore = false, bool $upsert = false)`: Inserts a row. `ignore`
  skips a row that conflicts with an existing one, `upsert` updates it.
- `update(array $values, int|string $key)`: Updates a row, in a transaction when the values are
  spread over several tables.
- `delete(int|string|array $key)`: Deletes a row.
- `truncate()` and `drop()`: Empty and drop the table.



## Custom model and query classes

The model and the query of a record can be specialized with the `model_class` and `query_class`
parameters of `ConfigBuilder::add_record()`. A custom query class is a good place for the query
methods that you use often:

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\Query;

/**
 * @extends Model<Article>
 */
class ArticleModel extends Model
{
}

/**
 * @extends Query<Article>
 */
class ArticleQuery extends Query
{
    public self $online {
        get => $this->and([ 'is_online' => true ]);
    }

    public function ordered(int $direction = -1): static
    {
        return $this->order('date ' . ($direction < 0 ? 'DESC' : 'ASC'));
    }
}

/* @var $builder \ICanBoogie\ActiveRecord\ConfigBuilder */

$builder->add_record(Article::class, model_class: ArticleModel::class, query_class: ArticleQuery::class);
```

Once the models are created from that config, the queries of articles are `ArticleQuery`
instances:

<!-- doc-test: skip: the records of Getting started use the default query class -->
```php
<?php

namespace App;

$articles = Article::query()->online->ordered()->all;
```



## The model registry

A [ModelRegistry][] instantiates the models from their definitions, on demand. You can define many
models, they are only instantiated, along with their connection, when they are needed. It
implements [ModelProvider][], type against the interface when you only need models.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\ModelRegistry;

/* @var $config \ICanBoogie\ActiveRecord\Config */

$models = new ModelRegistry(new ConnectionRegistry($config->connections), $config->models);

$articles = $models->model_for_record(Article::class);
```

The same model is returned for the same record class. A `LogicException` is thrown for a record
class that is not defined.

The definitions are available with the `definitions` property, and `model_iterator()` iterates
over them, telling which models are instantiated:

```php
<?php

/* @var $models \ICanBoogie\ActiveRecord\ModelRegistry */

foreach ($models->model_iterator() as $record_class => $accessor) {
    if ($accessor->instantiated) {
        echo "The model of '$record_class' is instantiated.\n";
    }

    // $accessor->get() returns the model, instantiating it if needed.
}
```

To create the tables of the models, see [Installing models](ModelInstaller.md).



## The static model provider

[StaticModelProvider][] gives access to models from anywhere, through a factory of
[ModelProvider][]. It is used by `ActiveRecord::model()`, `query()`, and `where()`. The factory is
invoked once, the first time a model is requested, and the models are cached.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\StaticModelProvider;

/* @var $models \ICanBoogie\ActiveRecord\ModelRegistry */

StaticModelProvider::set(fn() => $models);

$articles = StaticModelProvider::model_for_record(Article::class);
```

`set()` replaces the factory and clears the cache, and `reset()` removes the factory.



[Connection]:          ../../lib/ActiveRecord/Connection.php
[Model]:               ../../lib/ActiveRecord/Model.php
[ModelProvider]:       ../../lib/ActiveRecord/ModelProvider.php
[ModelRegistry]:       ../../lib/ActiveRecord/ModelRegistry.php
[Query]:               ../../lib/ActiveRecord/Query.php
[RecordNotFound]:      ../../lib/ActiveRecord/RecordNotFound.php
[Schema]:              ../../lib/ActiveRecord/Schema.php
[Statement]:           ../../lib/ActiveRecord/Statement.php
[StaticModelProvider]: ../../lib/ActiveRecord/StaticModelProvider.php
[Table]:               ../../lib/ActiveRecord/Table.php
