# Query interface

The query interface provides different ways to retrieve data from the database. Using the query
interface, you can find records using a variety of methods and conditions; specify the order,
fields, grouping, limit, or the tables to join; check the existence of particular records; perform
various calculations.

Queries start from a model, with `$model->query()` or `$model->where()`, or from a record class,
with `Article::query()` or `Article::where()`. Query methods return the query, so they can be
chained. Records are retrieved with the `all`, `one`, `pairs`, or `rc` properties, or by iterating
over the query. Casting a query to a string renders its SQL.

The examples use the records of [Getting started](../GettingStarted.md): two articles, the first
one by alice, online, with a comment by bob; the second one by bob, offline.



## Conditions

The `where()` method specifies the conditions used to filter the records. It represents the `WHERE`
part of the SQL statement. Conditions can either be specified as a string, with or without
arguments, or as an array.



### Conditions specified as a string

Adding a condition to a query can be as simple as `where('is_online = 1')`. Building your own
conditions as string can leave you vulnerable to SQL injection, for instance
`where('user_id = ' . $_GET['user'])` is not safe. Always use placeholders when you can't trust the
source of your inputs:

```php
<?php

namespace App;

$articles = Article::where('is_online = ?', true)->all;
$articles = Article::where('is_online = ? AND user_id = ?', true, 1)->all;
```

`and()` is an alias of `where()`, which reads better when you add conditions:

```php
<?php

namespace App;

$articles = Article::where('is_online = ?', true)->and('user_id = ?', 1)->all;
```



### Conditions specified as an array

Conditions can also be specified as arrays, where _key_ is a column and _value_ its value:

```php
<?php

namespace App;

$articles = Article::where([ 'is_online' => true, 'user_id' => 1 ])->all;
```

An array of values makes a subset condition:

```php
<?php

namespace App;

echo Article::where([ 'nid' => [ 1, 2, 3 ] ]);
// … WHERE (`nid` IN(1,2,3))
```

The values of a subset are quoted and written in the statement, they are not passed as arguments.
A [query can be used as a subset](#using-a-query-as-a-subquery) too.

Prefixing a column with an exclamation mark negates the condition:

```php
<?php

namespace App;

echo Article::where([ '!user_id' => 1 ]);
// … WHERE (`user_id` != ?)

echo Article::where([ '!nid' => [ 1, 2 ] ]);
// … WHERE (`nid` NOT IN(1,2))
```



## Ordering

The `order()` method retrieves records in a specific order:

```php
<?php

namespace App;

$articles = Article::query()->order('date')->all;
$articles = Article::query()->order('date DESC, title')->all;
```

A column prefixed with a minus sign is sorted in descending order, `order('-date, title')` is the
same as `order('date DESC, title')`.

On MySQL, records can also be ordered by the values of a column, using `FIELD()`:

<!-- doc-test: mysql -->
```php
<?php

namespace App;

$articles = Article::where([ 'nid' => [ 1, 2 ] ])->order('nid', [ 2, 1 ])->all;
```



## Grouping data

The `group()` method specifies the `GROUP BY` clause, and the `having()` method specifies the
`HAVING` clause, the conditions on the groups. `having()` takes conditions as `where()` does. The
following example counts the articles of the users who wrote at least one:

```php
<?php

namespace App;

$counts = Article::query()
    ->select('user_id, COUNT(nid)')
    ->group('user_id')
    ->having('COUNT(nid) > 0')
    ->pairs; // [ 1 => 1, 2 => 1 ]
```

On SQLite, an argument compared with an expression such as `COUNT(nid)` is compared as text, see
[Database engines](../Engines.md#queries).



## Skipping and taking rows

Use the `skip()` method to specify the number of rows to skip before fetching, and the `take()`
method to specify the number of rows to take:

```php
<?php

namespace App;

$articles = Article::query()->order('-date')->skip(10)->take(10)->all;
```



## Selecting specific fields

By default, only the fields of the queried record are selected, that is the fields of its table and
of its parents' tables (e.g. `SELECT article.*, node.*`), and records are instances of the
[ActiveRecord][] class defined by the model. The fields of joined tables are not selected, so they
can't overwrite the record's fields of the same name.

The following example finds the articles with a comment containing "Great". The records are
`Article` instances, and since the columns of `comments` aren't selected, `comment.user_id`
doesn't overwrite `Article::$user_id`:

```php
<?php

namespace App;

$articles = Article::query()
    ->join(record: Comment::class)
    ->where('comment.body LIKE ?', '%Great%')
    ->all;

echo $articles[0]->user->username; // alice
```

The `select()` method specifies the fields to select, in which case each row of the result set is
returned as an array, unless a fetch mode is defined. Because the `SELECT` string is used _as is_,
SQL expressions can be used. Use `select('*')` to get the fields of joined tables too.

```php
<?php

namespace App;

$rows = Article::query()->select('nid, title, UPPER(title) AS shout')->all;
// [ [ 'nid' => 1, 'title' => "Hello world", 'shout' => "HELLO WORLD" ], … ]
```



## Joining tables

The `join()` method specifies a `JOIN` clause. A record class, a subquery, or a raw string can be
used to specify the join. The method can be used multiple times to create multiple joins.



### Joining tables using a record class

The table of the model of the record class is joined. The tables are joined `USING` the primary key
of the queried model if the joined table has that column, otherwise the primary key of the joined
model. The following options are available:

- `mode`: The join mode. Default: `INNER`.
- `as`: The alias of the joined table. Default: The alias of the joined model.
- `on`: The column used to join the tables.

```php
<?php

namespace App;

echo Article::query()->join(record: Comment::class, mode: 'LEFT', as: 'c');
// … LEFT JOIN `comments` AS `c` USING(`nid`)
```

The model is obtained from the model provider of the queried model.



### Joining tables using a subquery

A query can be joined as a subquery. The following options are available:

- `mode`: The join mode. Default: `INNER`.
- `as`: The alias of the subquery. Default: The alias of the model of the subquery.
- `on`: The column used to join the subquery, which must exist in both. Default: The primary key of
  the model of the subquery. Depending on the columns, the method uses `USING` or `ON`.

The following example orders the articles by their number of comments. The `LEFT` join mode keeps
the articles without comments:

```php
<?php

namespace App;

$comment_counts = Comment::query()
    ->select('nid, COUNT(id) AS comment_count')
    ->group('nid');

$rows = Article::query()
    ->join(query: $comment_counts, on: 'nid', mode: 'LEFT')
    ->select('title, comment_count')
    ->order('comment_count DESC')
    ->all;
```



### Joining tables using a raw string

Finally, a join can be specified using a raw string, which is included _as is_ in the statement.
The `{prefix}` placeholder is replaced with the table name prefix of the connection.

```php
<?php

namespace App;

$articles = Article::query()
    ->join(expression: 'INNER JOIN {prefix}users AS author ON author.id = article.user_id')
    ->where('author.username = ?', "alice")
    ->all;
```



## Retrieving data

The `find()` method of the [model](Model.md#finding-records) retrieves records using their
primary key. The following methods and properties retrieve the records matching a query.



### Retrieving data by iteration

Queries are traversable, it's the easiest way to retrieve the rows of a result set. The rows are
fetched in batches of 1000, use `batch_size()` to change the size of the batches.

```php
<?php

namespace App;

foreach (Article::where([ 'is_online' => true ])->batch_size(100) as $article) {
    echo "$article->title\n";
}
```



### Retrieving the complete result set

The `all` property retrieves the complete result set as an array, and the `all()` method does the
same with a specific fetch mode:

```php
<?php

namespace App;

$articles = Article::where([ 'is_online' => true ])->order('-date')->all;
$rows = Article::where([ 'is_online' => true ])->order('-date')->all(\PDO::FETCH_ASSOC);
```



### Retrieving a single record

The `one` property retrieves a single record, or `false` if there is none. The `one()` method does
the same with a specific fetch mode. The number of records to retrieve is automatically limited to
1.

```php
<?php

namespace App;

$latest = Article::query()->order('-date')->one;
$row = Article::query()->order('-date')->one(\PDO::FETCH_ASSOC);
```



### Retrieving key/value pairs

The `pairs` property retrieves key/value pairs when selecting two columns, the first column is the
key and the second its value:

```php
<?php

namespace App;

$titles = Article::query()->select('nid, title')->pairs;
// [ 1 => "Hello world", 2 => "Work in progress" ]
```



### Retrieving the first column of the first row

The `rc` property retrieves the first column of the first row. The number of records to retrieve is
automatically limited to 1.

```php
<?php

namespace App;

$title = Article::query()->select('title')->order('-views')->rc; // Hello world
```



## Defining the fetch mode

The fetch mode is usually selected by the query interface, but the `mode()` method can specify it.
It accepts the same arguments as
[PDOStatement::setFetchMode](https://www.php.net/manual/en/pdostatement.setfetchmode.php).

```php
<?php

namespace App;

$rows = Article::query()->select('nid, title')->mode(\PDO::FETCH_NUM)->all;
// [ [ 1, "Hello world" ], [ 2, "Work in progress" ] ]
```



## Checking the existence of records

The `exists()` method checks the existence of records using their primary key. With multiple keys,
it returns `true` when all the records exist, `false` when none exist, and an array otherwise.

```php
<?php

namespace App;

var_dump(Article::query()->exists(1));            // true
var_dump(Article::query()->exists(1, 2));         // true
var_dump(Article::query()->exists([ 1, 2, 99 ])); // [ 1 => true, 2 => true, 99 => false ]
```

The `exists` property is `true` if at least one record matches the query:

```php
<?php

namespace App;

var_dump(Article::where([ 'user_id' => 1 ])->exists); // true
```



## Counting

The `count` property is the number of records matching a query:

```php
<?php

namespace App;

echo Article::query()->count;                         // 2
echo Article::where([ 'is_online' => true ])->count;  // 1
echo Article::query()->join(record: Comment::class)->count; // 1
```

The `count()` method returns an array with the number of records for each value of a column:

```php
<?php

namespace App;

$counts = Article::query()->count('user_id'); // [ 1 => 1, 2 => 1 ]
```



## Calculations

The `average()`, `minimum()`, `maximum()` and `sum()` methods compute, for a column, its average
value, its minimum value, its maximum value, and its sum. They take the conditions of the query
into account.

```php
<?php

namespace App;

echo Article::query()->sum('views');                                // 42
echo Article::query()->maximum('date');                             // 2026-10-11
echo Article::where([ 'is_online' => true ])->average('views');     // 42
```



## Some useful properties

The following properties might be helpful, especially when you use a query in another query:

- `conditions`: The conditions, as a list of strings.
- `conditions_args`: The arguments to the conditions.
- `joins`: The `JOIN` clauses, as a list of strings.
- `args`: All the arguments of the query: those of the joins, the conditions, and the `HAVING`
  clause.
- `model`: The model of the query.



## Using a query as a subquery

A query can be used as a subset in the conditions of another query. The following example finds
the articles commented by bob:

```php
<?php

namespace App;

$commented_by_bob = Comment::where([ 'user_id' => 2 ])->select('nid');

$articles = Article::where([ 'nid' => $commented_by_bob ])->all;

// Or, as a string, with its arguments.
$articles = Article::where("nid IN ($commented_by_bob)", $commented_by_bob->args)->all;
```



## Deleting the records matching a query

The records matching a query can be deleted using the `delete()` method:

```php
<?php

namespace App;

Comment::where([ 'user_id' => 2 ])->delete();
```

On MySQL, the number of records to delete can be limited with `take()`, and tables can be joined to
decide which records to delete. When tables are joined, the records are only deleted from the table
of the query, unless other tables are specified:

<!-- doc-test: mysql -->
```php
<?php

namespace App;

Comment::query()->order('-id')->take(10)->delete();

Comment::query()
    ->join(record: Article::class)
    ->where('article.user_id = ?', 1)
    ->delete();
```



[ActiveRecord]: ../../lib/ActiveRecord.php
