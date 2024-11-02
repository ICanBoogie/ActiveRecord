# Query Interface

The query interface provides different ways to retrieve data from the database. Using the query
interface, you can find records using a variety of methods and conditions; specify the order,
fields, grouping, limit, or the tables to join; use dynamic or scoped filters; check the existence
or particular records; perform various calculations.

Records can be retrieved in various ways, especially using the `all`, `one`, `pairs` or `rc` magic
properties.





## Summary

[Conditions](#conditions)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where('is_online = ?', true);
$query->where([ 'is_online' => true, 'is_home_excluded' => false ]);
$query->where('site_id = 0 OR site_id = ?', 1)->and('language = "" OR language = ?', "fr");

# Sets

$query->where([ 'order_count' => [ 1, 2, 3 ] ]);
$query->where([ '!order_count' => [ 1, 2, 3 ] ]); # NOT

# Query extensions

$query->visible;
$query->own->visible->ordered;
```

[Grouping](#grouping-data) and [ordering](#ordering)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->group('date(created)')->order('created');
$query->group('date(created)')->having('created > ?', new DateTime('-1 month'))->order('created');
```

[Rows range](#specify-the-number-of-rows-to-skip-and-take)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->skip(100)->take(10);
```

[Fields selection](#selecting-specific-fields)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->select('nid, created, title');
$query->select('nid, created, CONCAT_WS(":", title, language)');
```

[Joins](#joining-tables)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->join(query: $subquery, on: 'nid');
$query->join(with: Content::class);
$query->join(expression: 'INNER JOIN contents USING(nid)');
```

[Retrieving data](#retrieving-data)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->all;
$query->order('created DESC')->all(PDO::FETCH_ASSOC);
$query->order('created DESC')->mode(PDO::FETCH_ASSOC)->all;
$query->order('created DESC')->one;
$query->select('nid, title')->pairs;
$query->select('title')->rc;
```

[Testing object existence](#checking-the-existence-of-records)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->exists;
$query->exists(1, 2, 3);
$query->exists([ 1, 2, 3 ]);
$query->where('author = ?', 'madonna')->exists;
```

[Calculations](#calculations)

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->count;
$query->count('is_online'); // count is_online = 0 and is_online = 1
$query->average('score');
$query->minimum('age');
$query->maximum('age');
$query->sum('comments_count');
```





## Conditions

The `where()` method specifies the conditions used to filter the records. It represents the
`WHERE`-part of the SQL statement. Conditions can either be specified as a string, as a list of
arguments or as an array.





### Conditions specified as a string

Adding a condition to a query can be as simple as `$query->where('is_online = 1')`. This would
return all the records where the `is_online` field equals `1`.

__Warning:__ Building your own conditions as string can leave you vulnerable to SQL injection
exploits. For instance, `$query->where('is_online = ' . $_GET['online']);` is not safe. Always use
placeholders when you can't trust the source of your inputs:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where('is_online = ?', true);
```

Of course, you can use multiple conditions:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where('is_online = ? AND is_home_excluded = ?', true, false);
```

`and()` is alias to `where()` and should be preferred when linking adding conditions:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where('is_online = ?', true)->and('is_home_excluded = ?', false);
```





### Conditions specified as an array (or list of arguments)

Conditions can also be specified as arrays:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'is_online' => true, 'is_home_excluded' => false ]);
```





### Subset conditions

Records belonging to a subset can be retrieved using an array as condition value:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'orders_count' => [ 1, 3, 5 ] ]);
```

This generates something like: `... WHERE (orders_count IN (?,?,?))`, with the arguments available
in `$query->args`.





### Modifiers

When conditions are specified as an array, it is possible to modify the comparing function.
Prefixing a field name with an exclamation mark uses the _not equal_ operator.

The following example demonstrates how to search for records where the `order_count` field is
different from "2":

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ '!order_count' => 2 ]);
```

```
… WHERE `order_count` != 2
```

This also works with subsets:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ '!order_count' => [ 1,3,5 ] ]);
```

```
… WHERE `order_count` NOT IN(1, 3, 5)
```




## Ordering

The `order()` method retrieves records in a specific order.

The following example demonstrates how to get records in the ascending order of their creation date:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->order('created');
```

A direction can be specified:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->order('created ASC');
# or
$query->order('created DESC');
```

Multiple fields can be used while ordering:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->order('created DESC, title');
```

Records can also be ordered by field:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'nid' => [ 1, 2, 3 ] ])->order('nid', [ 2, 3, 1 ]);
# or
$query->where([ 'nid' => [ 1, 2, 3 ] ])->order('nid', 2, 3, 1);
```





## Grouping data

The `group()` method specifies the `GROUP BY` clause.

The following example demonstrates how to retrieve the first record of records grouped by day:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->group('DATE(created)')->order('created');
```





### Filtering groups

The `having()` method specifies the `HAVING` clause, which specifies the conditions of the `GROUP
BY` clause.

The following example demonstrates how to retrieve the first record created by day for the past
month:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->group('DATE(created)')->having('created > ?', new DateTime('-1 month'))->order('created');
```





## Specify the number of rows to skip and take

Use the `skip()` method to specify the number of rows to skip before fetching, and use the `take()`
method to specify the number of rows to take while fetching.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->skip(100)->take(10);
```




## Selecting specific fields

By default, all fields are selected (`SELECT *`) and records are instances of the [ActiveRecord][]
class defined by the model. The `select()` method selects only a subset of fields from the result
set, in which case each row of the result set is returned as an array, unless a fetch mode is
defined.

The following example demonstrates how to get the identifier, creation date, and title of records:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->select('nid, created, title');
```

Because the `SELECT` string is used _as is_ to build the query, complex SQL statements can be used:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->select('nid, created, CONCAT_WS(":", title, language)');
```





## Joining tables

The `join()` method specifies the `JOIN` clause. A raw string or a model identifier can be used to
specify the join. The method can be used multiple times to create multiple joins.





### Joining tables using a subquery

A query can be joined as a subquery. The following options are available:

- `mode`: Specifies the join mode. Default: `INNER`.
- `as`: Alias for the subquery. Default: The alias of the model associated with the query.
- `on`: The column used for the conditional expression. Depending on the columns available, the
  method tries to determine the best solution between `ON` and `USING`.

The following example demonstrates how to fetch users and order them by the number of online
articles they've published since last year. We use the join mode `LEFT` so that users that did not
publish articles are fetched as well.

```php
<?php

/* @var $articles \ICanBoogie\ActiveRecord\Model */
/* @var $users \ICanBoogie\ActiveRecord\Model */

$online_article_count = $articles
    ->where([ 'type' => 'articles', 'created_at' => new DateTime('-1 year') ])
    ->select('user_id, COUNT(node_id) AS online_article_count')
    ->online
    ->group('user_id');

$users = $users
    ->query()
    ->join(query: $online_article_count, on: 'user_id', mode: 'LEFT')
    ->order('online_article_count DESC');
```





### Joining tables using a model

A join can be specified using a model or a model identifier, in which case, the relationship between
that model and the model associated with the query is used to create the join. The following options
are available:

- `mode`: Specifies the join mode. Default: `INNER`.
- `as`: Alias for the joining model. Default: The alias of the joining model.

The column character ":" is used to distinguish a model identifier from a raw fragment.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */
/* @var $contents_model \ICanBoogie\ActiveRecord\Model */

$query->join(with: ContentRecord::class);
$query->join(with: ContentRecord::class, mode: 'LEFT', as: 'cnt');
```

> **Note:** If a model identifier is provided, the model collection associated with the
> query's model is used to obtain the model.





### Joining tables using a raw string

Finally, a join can be specified using a raw string, which will be included _as is_ in the final SQL
statement.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->join(expression: 'INNER JOIN `contents` USING(`nid`)');
```





## Retrieving data

There are many ways to retrieve data. We have already seen the `find()` method, which can be used to
retrieve records using their identifier. The following methods or magic properties work with
conditions.





### Retrieving data by iteration

Queries are traversable, it's the easiest way to retrieve the rows of a result set:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

foreach ($query->where('is_online = 1') as $node) {
    // …
}
```





### Retrieving the complete result set

The magic property `all` retrieves the complete result set as an array:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$array = $query->all;
$array = $query->visible->order('created DESC')->all;
```

The `all()` method retrieves the complete result set using a specific fetch mode:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$array = $query->all(\PDO::FETCH_ASSOC);
$array = $query->visible->order('created DESC')->all(\PDO::FETCH_ASSOC);
```





### Retrieving a single record

The `one` magic property retrieves a single record:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$record = $query->one;
$record = $query->order('created DESC')->one;
```

The `one()` method retrieves a single record using a specific fetch mode:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$record = $query->one(\PDO::FETCH_ASSOC);
$record = $query->order('created DESC')->one(\PDO::FETCH_ASSOC);
```

Note: The number of records to retrieve is automatically limited to 1.





### Retrieving key/value pairs

The `pairs` magic property retrieves key/value pairs when selecting two columns, the first column is
the key and the second its value.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->select('nid, title')->pairs;
```

The result is similar to the following example:

```
array
  34 => string 'Créer un nuage de mots-clé' (length=28)
  57 => string 'Générer à la volée des miniatures avec mise en cache' (length=56)
  307 => string 'Mes premiers pas de développeur sous Ubuntu 10.04 (Lucid Lynx)' (length=63)
  ...
```





### Retrieving the first column of the first row

The `rc` magic property retrieves the first column of the first row.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$title = $query->select('title')->rc;
```

Note: The number of records to retrieve is automatically limited to 1.





## Defining the fetch mode

The fetch mode is usually selected by the query interface but the `mode` can be used to specify it.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->select('nid, title')->mode(\PDO::FETCH_NUM);
```

The `mode()` method accepts the same arguments as the
[PDOStatement::setFetchMode](http://php.net/manual/fr/pdostatement.setfetchmode.php) method.

As we have seen in previous examples, the fetch mode can also be specified when fetching data with
the `all()` and `one()` methods.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$array = $query->order('created DESC')->all(\PDO::FETCH_ASSOC);
$record = $query->order('created DESC')->one(\PDO::FETCH_ASSOC);
```





## Checking the existence of records

The `exists()` method checks the existence of a record, it queries the database just like `find()`
but returns `true` when a record is found and `false` otherwise.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->exists(1);
```

The method accepts multiple identifiers in which case it returns `true` when all the records exist,
`false` when all the record don't exist, and an array otherwise.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->exists(1, 2, 999);
# or
$query->exists([ 1, 2, 999 ]);
```

The method would return the following result if records "1" and "2" exist but not record "999".

```
array
  1 => boolean true
  2 => boolean true
  999 => boolean false
```

The `exists` magic property is `true` if at least one record matching the specified conditions
exists, `false` otherwise.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'author' => 'Madonna' ])->exists;
```





## Counting

The `count` magic property is the number of records in a matching a query.

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->count;
```

Or on a query:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'firstname' => 'Ryan' ])->count;
```

Of course, all query methods can be combined:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'firstname' => 'Ryan' ])->join(with: Content::class)->and('YEAR(date) = 2011')->count;
```

The `count()` method returns an array with the number of recond for each value of a field:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->count('is_online');
```

```
array
  0 => string '35' (length=2)
  1 => string '145' (length=3)
```

In this example, there are 35 record online and 145 offline.





## Calculations

The `average()`, `minimum()`, `maximum()` and `sum()` methods are respectively used, for a column,
to compute its average value, its minimum value, its maximum value and its sum.

All calculation methods work directly on the query:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->average('price');
```

And on a query:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'category' => 'Toys' ])->average('price');
```

Of course, all query methods can be combined:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query->where([ 'category' => 'Toys' ])->join(with: Content::class)->and('YEAR(date) = 2011')->average('price');
```





## Some useful properties

The following properties might be helpful, especially when you are using the Query interface to
create a query string to be used in the subquery of another query:

- `conditions`: The conditions rendered as a string.
- `conditions_args`: The arguments to the conditions.
- `model`: The model associated with the query.





## Using a query as a subquery

The following example demonstrates how a query on some taxonomy queries can be used as a subquery
to obtain only the online articles in a "music" category:

```php
<?php

/* @var $taxonomy_terms_nodes \ICanBoogie\ActiveRecord\Query */
/* @var $articles \ICanBoogie\ActiveRecord\Query */

$taxonomy_query = $taxonomy_terms_nodes
    ->where([

        'termslug' => "music",
        'vocabularyslug' => "category",
        'constructor' => "articles"

    ])
    ->join(with: Vocabulary::class)
    ->join(with: VocabularyScope::class)
    ->select('nid');

$matches = $articles
    ->where([ 'is_online' => true ])
    ->and("nid IN ($taxonomy_query)", $taxonomy_query->conditions_args)
    ->all;

# or

$matches = $articles
    ->where([ 'is_online' => true, 'nid' => $taxonomy_query ])
    ->all;
```





## Deleting the records matching a query

The records matching a query can be deleted using the `delete()` method:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query
    ->where([ 'is_deleted' => true, 'uid' => 123 ])
    ->take(10)
    ->delete();
```

You might need to join tables to decide which record to delete, in which case you might want to
define in which tables the records should be deleted. The following example demonstrates how to
delete the nodes and comments of nodes belonging to user 123 and marked as deleted:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query
    ->where([ 'is_deleted' => true, 'uid' => 123 ])
    ->join(with: Node::class)
    ->delete('comments, nodes');
```

When using `join()` the table associated with the query is used by default. The following example
demonstrates how to delete nodes that lack content:

```php
<?php

/* @var $query \ICanBoogie\ActiveRecord\Query */

$query
    ->join(with: Content::class, mode: 'LEFT')
    ->where('content.nid IS NULL')
    ->delete();
```





[ActiveRecord](../../lib/ActiveRecord.php)
