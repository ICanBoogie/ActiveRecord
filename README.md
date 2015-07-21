# Active Record

[![Release](https://img.shields.io/packagist/v/ICanBoogie/activerecord.svg)](https://packagist.org/packages/icanboogie/activerecord)
[![Build Status](https://img.shields.io/travis/ICanBoogie/ActiveRecord.svg)](http://travis-ci.org/ICanBoogie/ActiveRecord)
[![HHVM](https://img.shields.io/hhvm/icanboogie/activerecord.svg)](http://hhvm.h4cc.de/package/icanboogie/activerecord)
[![Code Quality](https://img.shields.io/scrutinizer/g/ICanBoogie/ActiveRecord.svg)](https://scrutinizer-ci.com/g/ICanBoogie/ActiveRecord)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/ActiveRecord.svg)](https://coveralls.io/r/ICanBoogie/ActiveRecord)
[![Packagist](https://img.shields.io/packagist/dt/icanboogie/activerecord.svg)](https://packagist.org/packages/icanboogie/activerecord)

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





### Acknowledgments

The implementation of the query interface is vastly inspired by
[Ruby On Rails' Active Record Query Interface](http://guides.rubyonrails.org/active_record_querying.html).





## Getting started

Unless you bound **ActiveRecord** to [ICanBoogie][] using the [icanboogie/bind-activerecord][]
package, you need to define the lazy getter for the `activerecord_cache` property of
the [Model][] class.

The following code should do the trick:

```php
<?php

use ICanBoogie\ActiveRecord\RuntimeActiveRecordCache;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\Prototype;

Prototype::from(Model::class)['lazy_get_activerecord_cache'] = function(Model $model) {

	return new RuntimeActiveRecordCache;

};
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

The `ConnectionOptions::TABLE_NAME_PREFIX` option specifies the prefix for all the tables name
of the connection. Thus, if the `icybee` prefix is defined the `nodes` table is
renamed as `icybee_nodes`.

The `{table_name_prefix}` placeholder is replaced in queries by the prefix:

```php
<?php

$statement = $connection('SELECT * FROM `{table_name_prefix}nodes` LIMIT 10');
```





### Defining the charset and collate to use

The `ConnectionOptions::CHARSET_AND_COLLATE` option specify the charset and the collate
of the connection in a single string e.g. "utf8/general_ci" for the "utf8" charset and the
"utf8_general_ci" collate.

The `{charset}` and `{collate}` placeholders are replaced in queries:

```php
<?php

$connection('ALTER TABLE nodes CHARACTER SET "{charset}" COLLATE "{collate}"');
```




### Specifying a time zone

The `ConnectionOptions::TIMEZONE` option specifies the time zone of the connection.





## Model overview

A _model_ is an object-oriented representation of a database table, or a group of tables.
A model is used to create, update, delete and query records. Models are instances of the [Model][]
class, and usually implement a specific business logic.

```php
<?php

namespace App\Modules\Nodes;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;

class NodeModel extends Model
{
	// …
}

class Node extends ActiveRecord
{
	public $id;
	public $title;
	public $number;

	// …
}

$models = new ModelCollection($connections, [

	'nodes' => [

		Model::ACTIVERECORD_CLASS => Node::class,
		Model::SCHEMA => [

			'fields' => [

				'id' => 'serial',
				'title' => [ 'varchar', 80 ],
				'number' => [ 'integer', 'unsigned' => true ]

			]
		]
	]
]);

$models->install();

$node_model = $models['nodes'];

$node = Node::from([

	'title' => "My first node",
	'number' => 123

], [ $node_model ]);
// ^ because we don't use a model provider yet, we need to specify the model to the active record

# or

$node = $node_model->new([

	'title' => "My first node",
	'number' => 123

]);

$id = $node->save();

echo "Saved node, got id: $id\n";
```





### Defining the database connection

The `CONNECTION` key specifies the database connection, an instance of the [Connection][] class.





### Defining the Active Record class

The `ACTIVERECORD_CLASS` key specifies the class used to instantiate the active records of the
model.





### Defining the name of the table

The `NAME` key specifies the name of the table. If a table prefix is defined by the connection,
it is used to prefix the table name. The `name` and `unprefixed_name` properties returns the
prefixed name and original name of the table:

```php
<?php

echo "table name: {$model->name}, original: {$model->unprefixed_name}.";
```

The `{self}` placeholder is replaced in queries by the `name` property:

```php
<?php

$stmt = $model('SELECT * FROM `{self}` LIMIT 10');
```





### Defining the schema of the model

The columns and properties of the table are defined with a schema, which is specified by the
`SCHEMA` attribute.

The `fields` key specifies the columns of the table. A key defines the name of the column, and a
value defines the properties of the column. Most column types use the following basic definition
pattern:

```
'<identifier>' => '<type_and_default_options>'
# or
'<identifier>' => [ '<type>', <size> ];
```

The following types are available: `blob`, `char`, `integer`, `text`, `varchar`,
`bit`, `boolean`, `date`, `datetime`, `time`, `timestamp`, `year`, `enum`, `double` et `float`. The
`serial` and `foreign` special types are used to defined auto incrementing primary keys and foreign
keys:

```php
<?php

[
	'nid' => 'serial', // bigint(20) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`nid`)
	'uid' => 'foreign' // bigint(20) unsigned NOT NULL, KEY `uid` (`uid`)
];
```

The size of the field can be defined as an integer for the `blob`, `char`, `integer`, `varchar` and
`bit` types:

```php
<?php

[
	'title' => 'varchar', // varchar(255) NOT NULL
	'slug' => [ 'varchar', 80 ], // varchar(80) NOT NULL
	'weight' => 'integer', // int(11) NOT NULL
	'small_count' => [ 'integer', 8 ] // int(8) NOT NULL,
	'price' => [ 'float', [ 10, 3 ] ] // float(10,3) NOT NULL
];
```

The size of the field can be defined using the qualifiers `tiny`, `small`, `medium`,
`big` or `long` for the `blob`, `char`, `integer`, `text` and `varchar` types:

```php
<?php

[
	'body' => [ 'text', 'long' ] // longtext NOT NULL
];
```

The qualifier `null` specifies that a field can be null, by default fields are not capable
of receiving the `null`:

```
[ 'varchar', 'null' => true ] // varchar(255)
[ 'integer', 'null' => true ] // int(11)
[ 'integer' ] // int(11) NOT NULL
```

The qualifier `unsigned` specifies that a numeric value is not signed:

```
[ 'integer' ] // int(11)
[ 'integer', 'unsigned' => true ] // int(10) unsigned
```

The qualifier `indexed` specifies that a field should be indexed:

```php
[
	'slug' => [ 'varchar', 'indexed' => true ], // varchar(255) NOT NULL, KEY `slug` (`slug`)
	'is_online' => [ 'boolean', 'indexed' => true ] // tinyint(1) NOT NULL, KEY `is_online` (`is_online`),

	'pageid' => [ 'foreign', 'indexed' => 'page-content' ], // bigint(20) unsigned NOT NULL
	'contentid' => [ 'foreign', 'indexed' => 'page-content' ], // bigint(20) unsigned NOT NULL, KEY `page-content` (`pageid`, `contentid`)
];
```

The qualifier `primary` specifies that a column is a primary key. A multi-column primary key is
created if multiple columns have the `primary` qualifier:

```php
[
	'vtid' => [ 'foreign', 'primary' => true ],
	'nid' => [ 'foreign', 'primary' => true ],
	'weight' => [ 'integer', 'unsigned' => true ]
];

// ADD PRIMARY KEY ( `vtid` , `nid` )
```




### Creating the table associated with a model

Once the model has been defined, its associated table can easily be created with the `install()`
method.

```php
<?php

$model->install();
```

The `is_installed()` method checks if a model has already been installed.

Note: The method only checks if the corresponding table exists, not if its schema is correct.

```php
<?php

if ($model->is_installed())
{
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

The `EXTENDING` attribute specifies the model to extend.

```php
<?php

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\DateTime;

$models = new ModelCollection($connections, [

	'nodes' => [

		Model::SCHEMA => [

			'fields' => [

				'nid' => 'serial',
				'title' => 'varchar'

			]
		]
	],
	
	'contents' => [

		Model::EXTENDING => 'nodes',
		Model::SCHEMA => [

			'fields' => [

				'body' => 'text',
				'date' => 'date'

			]
		]
	],

	'news' => [
	
		Model::EXTENDING => 'contents'

	]
];

$models['news']->save([

	'title' => "Testing!",
	'body' => "Testing...",
	'date' => DateTime::now()

]);
```

Contrary to tables, models are not required to define a schema if they extend another model, but
they may end with different parents.

In the following example the parent _table_ of `news` is `nodes` but its parent _model_ is
`contents`. That's because `news` doesn't define a schema and thus inherits the schema and some
properties of its parent model.

```php
<?php

echo $news->parent->id;       // nodes
echo $news->parent_model->id; // contents
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

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;

$models = new ModelCollection($this->connections, [

	'news' => [
	
		Model::BELONGS_TO => 'users',
		Model::SCHEMA => [
	
			'fields' => [

				'news_id' => 'serial',
				'uid' => 'foreign'
				// …
				
			]
		]
	],
	
	'users' => [
	
		Model::SCHEMA => [
    
            'fields' => [
    
                'uid' => 'serial',
                'name' => 'varchar'
                // …
                
            ]
        ]
	]

]);

$record = $news->one;

echo "{$record->title} belongs to {$record->user->name}.";
```





### One-to-many relation (has_many)

A one-to-many relation can be established between two models. For instance, an article having
many comments. The relation is specified with the `HAS_MANY` attribute or the `has_many()` method
of the parent model. A getter is added to the active record class of the model and returns a
[Query][] instance when it is accessed.

The following example demonstrates how a one-to-many relation can be established between the
"articles" and "comments" models, while creating the models:

```php
<?php

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;

// …

$models = new ModelCollection($connections, [

	'comments' => [

		Model::ACTIVERECORD_CLASS => Comment::class,
		Model::SCHEMA => [

			'fields' => [

				'comment_id' => 'serial',
				'article_id' => 'foreign',
				'body' => 'text'

			]
		]
	],

	'articles' => [

		Model::ACTIVERECORD_CLASS => Article::class,
		Model::HAS_MANY => 'comments',
		Model::SCHEMA => [

			'fields' => [

				'article_id' => 'serial',
				'title' => 'varchar'

			]
		]
	]

]);
```

The relation can also be established after the models are created using the `has_many` method:

```php
<?php

$articles = $models['articles'];
$comments = $models['comments'];

$articles->has_many($comments);
# or, if the model can be obtained with `get_model()`
$articles->has_many('comments');

# The local and foreign keys can be specified if they cannot be obtained from the models
$articles->has_many('comments', [ 'local_key' => 'article_id', 'related_key' => 'comment_id' ]);

# The name of the magic property can also be specified
$articles->has_many('comments', [ 'as' => 'article_comments' ]);
```

The following example demonstrates how all the comments of the author called "me"
can be retrieved in order from an article:

```php
<?php

$comments = $articles->one->comments->filter_by_author("me")->ordered->all;
```







## Active Records

An active record is an object-oriented representation of a record in a database. Usually, the
table columns are its public properties and it is not unusual that getters/setters and business
logic methods are implemented by its class.

The model managing the record needs to be specified when the instance is created. It can be
specified through the `__construct` method as a model identifier or a [Model][] instance, or by
defining the `MODEL_ID` constant, which is the favorite method since it can be easily
introspected.

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;

class Node extends ActiveRecord
{
	const MODEL_ID = "nodes";

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





### Saving an active record

Most properties of an active record are persistent. The `save()` method is used to save the active
record to the database. The model is clever enough to filter the properties.

```php
<?php

$record = $model[10];
$record->is_online = false;
$record->save();
```

Before a record is saved its `alter_persistent_properties()` is invoked to alter the properties
that will be sent to the model. One may extend the method to add, remove or alter properties
without altering its instance.





### Deleting an active record

The `delete()` method deletes the active record from the database:

```php
<?php

$record = $model[190];
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





## Query Interface

The query interface provides different ways to retrieve data from the database. Using the query
interface you can find records using a variety of methods and conditions; specify the order,
fields, grouping, limit, or the tables to join; use dynamic or scoped filters; check the existence
or particular records; perform various calculations.

Queries often starts from a model, in the following examples the `$model` variable is a reference
to a model managing _nodes_.





### Retrieving records from the database

To retrieve objects and values from the database several finder methods are provided. Each of these
methods defines the fragments of the database query. Complex queries can be created without having
to write any raw SQL.

The methods are:

* where
* select
* group
* having
* order
* limit
* offset
* join

All of the above methods return a [Query][] instance, allowing you to chain them.

Records can be retrieved in various ways, especially using the `all`, `one`, `pairs` or `rc`
magic properties. The `find()` method—used to retrieve a single record or a set of records—is the
most simple of them.





#### Retrieving a single record

Retrieving a single record using its primary key is really simple. You can either use the `find()`
method of the model, or use the model as an array.

```php
<?php

$article = $model->find(10);

# or

$article = $model[10];
```





#### Retrieving a set of records

Retrieving a set or records using their primary key is really simple too:

```php
<?php

$articles = $model->find([ 10, 32, 89 ]);

# or

$articles = $model->find(10, 32, 89);
```

The [RecordNotFound][] exception is thrown when a record could not be found. Its `records` property
can be used to know which records could be found and which could not.

Note: The records of the set are returned in the same order they are requested, this also applies
to the `records` property of the [RecordNotFound][] exception.





#### Records caching

Records retrieved using `find()` are cached, they are reused by subsequent calls. This also
applies to the array notation.

```php
<?php

$article = $model[12]; // '12' retrieved from database
$articles = $model->find(11, 12, 13); // '11' and '13' retrieved from database, '12' is reused.
```





### Conditions

The `where()` method specifies the conditions used to filter the records. It represents
the `WHERE`-part of the SQL statement. Conditions can either be specified as a string, as a
list of arguments or as an array.





#### Conditions specified as a string

Adding a condition to a query can be as simple as `$model->where('is_online = 1');`. This
would return all the records where the `is_online` field equals "1".

__Warning:__ Building you own conditions as string can leave you vulnerable to SQL injection
exploits. For instance, `$model->where('is_online = ' . $_GET['online']);` is not safe. Always
use placeholders when you can't trust the source of your inputs:

```php
<?php

$model->where('is_online = ?', $_GET['online']);
```

Of course you can use multiple conditions:

```php
<?php

$model->where('is_online = ? AND is_home_excluded = ?', $_GET['online'], false);
```

`and()` is alias to `where()` and should be preferred when linking adding conditions:

```php
<?php

$model->where('is_online = ?', $_GET['online'])->and('is_home_excluded = ?', false);
```





#### Conditions specified as an array (or list of arguments)

Conditions can also be specified as arrays:

```php
<?php

$model->where([ 'is_online' => $_GET['online'], 'is_home_excluded' => false ]);
```





#### Subset conditions

Records belonging to a subset can be retrieved using an array as condition value:

```php
<?php

$model->where([ 'orders_count' => [ 1,3,5 ] ]);
```

This generates something like: `... WHERE (orders_count IN (1,3,5))`.





#### Modifiers

When conditions are specified as an array it is possible to modify the comparing function.
Prefixing a field name with an exclamation mark uses the _not equal_ operator.

The following example demonstrates how to search for records where the `order_count` field is
different than "2":

```php
<?php

$model->where([ '!order_count' => 2 ]);
```

```
… WHERE `order_count` != 2
```

This also works with subsets:

```php
<?php

$model->where([ '!order_count' => [ 1,3,5 ] ]);
```

```
… WHERE `order_count` NOT IN(1, 3, 5)
```





#### Dynamic filters

Conditions can also be specified as methods, prefixed by `filter_by_` and separated by `_and_`:

```php
<?php

$model->filter_by_slug('creer-nuage-mots-cle');
$model->filter_by_is_online_and_uid(true, 3);
```

Is equivalent to:

```php
<?php

$model->where([ 'slug' => 'creer-nuage-mots-cle' ]);
$model->where([ 'is_online' => true, 'uid' => 3 ]);
```





#### Scopes

Scopes can be viewed as model defined filters. Models can define their own filters,
inherit filters from their parent class and override them. For instance, this is how a
`similar_site`, `similar_language` and `visible` scopes could be defined:

```php
<?php

namespace Website\Nodes;

use ICanBoogie\Core;
use ICanBoogie\ActiveRecord\Query;

class Model extends \ICanBoogie\ActiveRecord\Model
{
	// …

	protected function scope_similar_site(Query $query, $siteid=null)
	{
		return $query->and('siteid = 0 OR siteid = ?', $siteid !== null ? $siteid : Core::get()->site->siteid);
	}

	protected function scope_similar_language(Query $query, $language=null)
	{
		return $query->and('language = "" OR language = ?', $language !== null ? $language : Core::get()->site->language);
	}

	protected function scope_visible(Query $query, $visible=true)
	{
		return $query->similar_site->similar_language->filter_by_is_online($visible);
	}

	// …
}
```

Now you can easily retrieve the first ten records that are visible on your website:

```php
<?php

$model->visible->limit(10);
```

Or retrieve the first ten French records:

```php
<?php

$model->similar_language('fr')->limit(10);
```





### Ordering

The `order()` method retrieves records in a specific order.

The following example demonstrates how to get records in the ascending order of their creation
date:

```php
<?php

$model->order('created');
```

A direction can be specified:

```php
<?php

$model->order('created ASC');
# or
$model->order('created DESC');
```

Multiple fields can be used while ordering:

```php
<?php

$model->order('created DESC, title');
```

Records can also be ordered by field:

```php
<?php

$model->where([ 'nid' => [ 1, 2, 3 ] ])->order('nid', [ 2, 3, 1 ]);
# or
$model->where([ 'nid' => [ 1, 2, 3 ] ])->order('nid', 2, 3, 1);
```





### Grouping data

The `group()` method specifies the `GROUP BY` clause.

The following example demonstrates how to retrieve the first record of records grouped by day:

```php
<?php

$model->group('date(created)')->order('created');
```





#### Filtering groups

The `having()` method specifies the `HAVING` clause, which specifies the conditions of the
`GROUP BY` clause.

The following example demonstrates how to retrieve the first record created by day for the past
month:

```php
<?php

$model->group('date(created)')->having('created > ?', new DateTime('-1 month'))->order('created')
```





### Limit and offset

The `limit()` method limits the number of records to retrieve.

```php
<?php

$model->limit(10); // retrieves the first 10 records
```

With two arguments, an offset can be specified:

```php
<?php

$model->limit(5, 10); // retrieves records from the 6th to the 16th
```

The offset can also be defined using the `offset()` method:

```php
<?php

$model->offset(5); // retrieves records from the 6th to the last
$model->limit(10)->offset(5);
```





### Selecting specific fields

By default all fields are selected (`SELECT *`) and records are instances of the [ActiveRecord](http://icanboogie.org/docs/namespace-ICanBoogie.ActiveRecord.html)
class defined by the model. The `select()` method selects only a subset of fields from
the result set, in which case each row of the result set is returned as an array, unless a fetch
mode is defined.

The following example demonstrates how to get the identifier, creation date and title of records:

```php
<?php

$model->select('nid, created, title');
```

Because the `SELECT` string is used _as is_ to build the query, complex SQL statements can be
used:

```php
<?php

$model->select('nid, created, CONCAT_WS(":", title, language)');
```





### Joining tables

The `join()` method specifies the `JOIN` clause. A raw string or a model identifier
can be used to specify the join. The method can be used multiple times to create multiple joins.





#### Joining tables using a subquery

A [Query][] instance can be joined as a subquery. The following options are available:

- `mode`: Specifies the join mode. Default: `INNER`.
- `as`: Alias for the subquery. Default: The alias of the model associated with the query.
- `on`: The column used for the conditional expression. Depending on the columns available, the
method tries to determine the best solution between `ON` and `USING`.

```php
<?php

// …

$update_query = $models['updates']
->select('updated_at, subscriber_id, update_hash')
->order('updated_at DESC');

$subscribers = $models['subscribers']
->join($update_query, [ 'on' => 'subscriber_id' ])
->group("`{alias}`.subscriber_id")
->all;
```

The following example demonstrates how to fetch the available categories and their usage by the
articles of a blog. We use the join mode `LEFT` so that categories with no articles are also
fetched.

```php
<?php

// …

$usage = $models['articles']
->select('category_id, COUNT(category_id) AS `usage`')
->group('category_id');

$categories = $models['categories']
->join($usage, [ 'as' => 'usage', 'on' => 'category_id', 'mode' => 'LEFT' ])
->all;
```







#### Joining tables using a model

A join can be specified using a model or a model identifier, in which case the relationship
between that model and the model associated with the query is used to create the join. The
following options are available:

- `mode`: Specifies the join mode. Default: `INNER`.
- `as`: Alias for the joining model. Default: The alias of the joining model.

The column character ":" is used to distinguish a model identifier from a raw fragment.

```php
<?php

$model->join($contents_model);
# or
$model->join(':contents');

$model->join(':contents', [ 'mode' => 'LEFT', 'as' => 'cnt' ]);
```

Note: If a model identifier is provided, the `get_model()` helper is used to object the model
instance. Checkout the "Patching" section for implementation details.





#### Joining tables using a raw string

Finally, a join can be specified using a raw string, which will be included _as is_ in the
final SQL statement.

```php
<?php

$model->join('INNER JOIN `contents` USING(`nid`)');
```





### Retrieving data

There are many ways to retrieve data. We have already seen the `find()` method, which can be used
to retrieve records using their identifier. The following methods or magic properties work with
conditions.





#### Retrieving data by iteration

Instances of [Query][] are traversable, it's the easiest way the retrieve the rows
of a result set:

```php
<?php

foreach ($model->where('is_online = 1') as $node)
{
	// …
}
```





#### Retrieving the complete result set

The magic property `all` retrieves the complete result set as an array:

```php
<?php

$array = $model->all;
$array = $model->visible->order('created DESC')->all
```

The `all()` method retrieves the complete result set using a specific fetch mode:

```php
<?php

$array = $model->all(\PDO::FETCH_ASSOC);
$array = $model->visible->order('created DESC')->all(\PDO::FETCH_ASSOC)
```





#### Retrieving a single record

The `one` magic property retrieves a single record:

```php
<?php

$record = $model->one;
$record = $model->visible->order('created DESC')->one
```

The `one()` method retrieves a single record using a specific fetch mode:

```php
<?php

$record = $model->one(\PDO::FETCH_ASSOC);
$record = $model->visible->order('created DESC')->one(\PDO::FETCH_ASSOC);
```

Note: The number of records to retrieve is automatically limited to 1.





#### Retrieving key/value pairs

The `pairs` magic property retrieves key/value pairs when selecting two columns, the first column
is the key and the second its value.

```php
<?php

$model->select('nid, title')->pairs;
```

Results are similar to the following example:

```
array
  34 => string 'Créer un nuage de mots-clé' (length=28)
  57 => string 'Générer à la volée des miniatures avec mise en cache' (length=56)
  307 => string 'Mes premiers pas de développeur sous Ubuntu 10.04 (Lucid Lynx)' (length=63)
  ...
```





#### Retrieving the first column of the first row

The `rc` magic property retrieves the first column of the first row.

```php
<?php

$title = $model->select('title')->rc;
```

Note: The number of records to retrieve is automatically limited to 1.





### Defining the fetch mode

The fetch mode is usually selected by the query interface but the `mode` can be used to specify
it.

```php
<?php

$model->select('nid, title')->mode(\PDO::FETCH_NUM);
```

The `mode()` method accepts the same arguments as the
[PDOStatement::setFetchMode](http://php.net/manual/fr/pdostatement.setfetchmode.php) method.

As we have seen in previous examples, the fetch mode can also be specified when fetching data
with the `all()` and `one()` methods.

```php
<?php

$array = $model->order('created DESC')->all(\PDO::FETCH_ASSOC);
$record = $model->order('created DESC')->one(\PDO::FETCH_ASSOC);
```





### Checking the existence of records

The `exists()` method checks the existence of a record, it queries the database just like `find()`
but returns `true` when a record is found and `false` otherwise.

```php
<?php

$model->exists(1);
```

The method accepts multiple identifiers in which case it returns `true` when all the
records exist, `false` when all the record don't exist, and an array otherwise.

```php
<?php

$model->exists(1, 2, 999)
# or
$model->exists([ 1, 2, 999 ]);
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

$model->filter_by_author('Madonna')->exists;
```

The `exists` magic property of the model is `true` if the modal has at least one record, `false`
otherwise.

```php
<?php

$model->exists;
```





### Counting

The `count` magic property is the number of records in a model or matching a query.

```php
<?php

$model->count;
```

Or on a query:

```php
<?php

$model->filter_by_firstname('Ryan')->count;
```

Of course, all query methods can be combined:

```php
<?php

$model->filter_by_firstname('Ryan')->join(':content')->where('YEAR(date) = 2011')->count;
```

The `count()` method returns an array with the number of recond for each value of a field:

```php
<?php

$model->count('is_online');
```

```
array
  0 => string '35' (length=2)
  1 => string '145' (length=3)
```

In this example, there are 35 record online and 145 offline.





### Calculations

The `average()`, `minimum()`, `maximum()` and `sum()` methods are respectively used, for a column,
to compute its average value, its minimum value, its maximum value and its sum.

All calculation methods work directly on the model:

```php
<?php

$model->average('price');
```

And on a query:

```php
<?php

$model->filter_by_category('Toys')->average('price');
```

Of course, all query methods can be combined:

```php
<?php

$model->filter_by_category('Toys')->join(':content')->where('YEAR(date) = 2011')->average('price');
```





### Some useful properties

The following properties might be helpful, especially when you are using the Query interface
to create a query string to be used in the subquery of another query:

- `conditions`: The conditions rendered as a string.
- `conditions_args`: The arguments to the conditions.
- `model`: The model associated with the query.





### Using a query as a subquery

The following example demonstrates how a query on some taxonomy models can be used as a subquery
to obtain only the online articles in a "music" category:

```php
<?php

// …

$taxonomy_query = $models['taxonomy.terms/nodes']
->join(':taxonomy.vocabulary')
->join(':taxonomy_vocabulary/scopes')
->where([

	'termslug' => "music",
	'vocabularyslug' => "category",
	'constructor' => "articles"

])
->select('nid');

$articles = $models['articles']
->filter_by_is_online(true)
->and("nid IN ($taxonomy_query)", $taxonomy_query->conditions_args)
->all;

# or

$articles = $models['articles']
->filter_by_is_online_and_nid(true, $taxonomy_query)
->all;
```





### Deleting the records matching a query

The records matching a query can be deleted using the `delete()` method:

```php
<?php

$models['nodes']
->filter_by_is_deleted_and_uid(true, 123)
->limit(10)
->delete();
```

You might need to join tables to decide which record to delete, in which case you might
want to define in which tables the records should be deleted. The following example demonstrates
how to delete the nodes and comments of nodes belonging to user 123 and marked as deleted:

```php
<?php

$models['comments']
->filter_by_is_deleted_and_uid(true, 123)
->join(':nodes')
->delete('comments, nodes');
```

When using `join()` the table associated with the query is used by default. The following
example demonstrates how to delete nodes that lack content:

```php
<?php

$models['nodes']
->join(':contents', [ 'mode' => 'LEFT' ])
->where('content.nid IS NULL')
->delete()
```





### Query interface summary

Retrieving records:

```php
<?php

$record = $model[10];
# or
$record = $model->find(10);

$records = $model->find(10, 15, 19);
# or
$records = $model->find([ 10, 15, 19 ]);
```

Conditions:

```php
<?php

$model->where('is_online = ?', true);
$model->where([ 'is_online' => true, 'is_home_excluded' => false ]);
$model->where('siteid = 0 OR siteid = ?', 1)->and('language = '' OR language = ?', "fr");

# Sets

$model->where([ 'order_count' => [ 1, 2, 3 ] ]);
$model->where([ '!order_count' => [ 1, 2, 3 ] ]); # NOT

# Dynamic filters

$model->filter_by_nid(1);
$model->filter_by_siteid_and_language(1, 'fr');

# Scopes

$model->visible;
$model->own->visible->ordered;
```

Grouping and ordering:

```php
<?php

$model->group('date(created)')->order('created');
$model->group('date(created)')->having('created > ?', new DateTime('-1 month'))->order('created');
```

Limits and offsets:

```php
<?php

$model->limit(10); // first 10 records
$model->limit(5, 10); // 6th to the 16th records

$model->offset(5); // from the 6th to the last
$model->offset(5)->limit(10);
```

Fields selection:

```php
<?php

$model->select('nid, created, title');
$model->select('nid, created, CONCAT_WS(":", title, language)');
```

Joins:

```php
<?php

$model->join($subquery, [ 'on' => 'nid' ]);
$model->join(':contents');
$model->join('INNER JOIN contents USING(nid)');
```

Retrieving data:

```php
<?php

$model->all;
$model->order('created DESC')->all(PDO::FETCH_ASSOC);
$model->order('created DESC')->mode(PDO::FETCH_ASSOC)->all;
$model->order('created DESC')->one;
$model->select('nid, title')->pairs;
$model->select('title')->rc;
```

Testing object existence:

```php
<?php

$model->exists;
$model->exists(1, 2, 3);
$model->exists([ 1, 2, 3 ]);
$model->where('author = ?', 'madonna')->exists;
```

Calculations:

```php
<?php

$model->count;
$model->count('is_online'); // count is_online = 0 and is_online = 1
$model->filter_by_is_online(true)->count; // count is_online = 1
$model->average('score');
$model->minimum('age');
$model->maximum('age');
$model->sum('comments_count');
```





## Providers

Providers are included to manage connections and models.





### The connections provider

The connections provider manages database connections.





#### Defining connections

Connection definitions can be specified while creating the [ConnectionCollection][]
instance.

```php
<?php

use ICanBoogie\ActiveRecord\ConnectionCollection;

$connections = new ConnectionCollection
([
	'one' => [

		'dsn' => 'sqlite::memory:'
	],

	'bad' => [

		'dsn' => 'mysql:dbname=bad_database' . uniqid()
	]
]);
```

Or after:

```php
<?php

$connections['two'] = [

	'dsn' => 'mysql:dbname=example',
	'username' => 'root',
	'password' => 'root'
];
```

You can modify a connection definition until it is established. A [ConnectionAlreadyEstablished][]
exception is thrown in attempt to modify the definition of an already established connection.





#### Obtaining a database connection

[ConnectionCollection][] instances are used as arrays. For instance, this is how you obtain a [Connection][]
instance, which represents a database connection:

```php
<?php

$one = $connections['one'];
```

Database connections are created on demand, so that you can define a hundred of them and they
will only be established when needed.

A [ConnectionNotDefined][] exception is thrown in attempt to obtain a connection
that is not defined.





#### Checking defined connections

Because [ConnectionCollection][] instances are used as arrays, the `isset()` function is used to check
if a connection is defined.

```php
<?php

if (isset($connections['one']))
{
	echo "The connection 'one' is defined.\n";
}
```

The `definitions` magic property returns the current connection definitions. The property is
_read-only_.

```php
<?php

foreach ($connections->definitions as $id => $definition)
{
	echo "The connection '$id' is defined.\n";
}
```





#### Established connections

An array with the established connections can be retrieved using the `established` magic property.
The property is _read-only_.

```php
<?php

foreach ($connections->established as $id => $connection)
{
	echo "The connection '$id' is established.\n";
}
```

The [ConnectionCollection][] instance itself can be used to traverse established connections.

```php
<?php

foreach ($connections as $id => $connection)
{
	echo "The connection '$id' is established.\n";
}
```





### The models provider

The models provider manages models.





#### Defining models

Model definitions can be specified while creating the [ModelCollection][] instance.

Note: You don't have to create the [Connection][] instances used by the models, you can use their
identifier which will get resolved when the model is needed.

Note: If `CONNECTION` is not specified the `primary` connection is used.

```php
<?php

use ICanBoogie\ActiveRecord\Model;
use ICanBoogie\ActiveRecord\ModelCollection;

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
```

Model definitions can be modified or added after the [ModelCollection][] instance has been created.

```php
<?php

use ICanBoogie\ActiveRecord\Model;

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

$nodes = $models['nodes'];
```

Models are instantiated on demand, so that you can define a hundred models an they will only by
instantiated, along with their database connection, when needed.

A [ModelNotDefined][] exception is thrown in attempts to obtain a model which is not defined.





#### Checking defined models

The `isset()` function checks if a model is defined.

```php
<?php

if (isset($models['nodes']))
{
	echo "The model 'node' is defined.\n";
}
```

The `definitions` magic property returns the current model definitions. The property is
_read-only_.

```php
<?php

foreach ($models->definitions as $id => $definition)
{
	echo "The model '$id' is defined.\n";
}
```





#### Instantiated models

An array with the instantiated models can be retrieved using the `instances` magic property. The
property is _read-only_.

```php
<?php

foreach ($models->instances as $id => $model)
{
	echo "The model '$id' has been instantiated.\n";
}
```





#### Installing / Uninstalling models

All the models managed by the provider can be installed and uninstalled with a single command
using the `install()` and `uninstall()` methods. The `is_installed()` method returns an array
of key/value pair where _key_ is a model identifier and _value_ `true` if the model is
installed, `false` otherwise.

```php
<?php

$models->install();
var_dump($models->is_installed()); // [ "nodes" => true, "contents" => true ]
$models->uninstall();
var_dump($models->is_installed()); // [ "nodes" => false, "contents" => false ]
```





## Records caching

By default, each model uses an instance of [RunTimeActiveRecordCache][] to cache its records.
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

Or using the `hooks` configuration:

```php
<?php

// config/hooks.php

return [

	'prototypes' => [

		'ICanBoogie\ActiveRecord\Model::lazy_get_activerecord_cache' => 'my_activerecord_cache_provider'

	]

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
- [StatementNotValid][]: Exception thrown in attempt to execute a statement that is not valid.
- [UnableToSetFetchMode][]: Exception thrown when the fetch mode of a statement fails to be set.





## Patching

### Retrieving models from a provider

The `get_model()` helper retrieves models using their identifier. It is used by active
records to retrieve their model when required, and by queries during joins. You need to patch
this helper according to your application logic because the default implementation only throws
`\RuntimeException`.

In the following example, the `get_model()` helper is patched to retrieve models from a provider
similar to the one we've seen in previous examples.

```php
<?php

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Helpers;

Helpers::patch('get_model', function($id) use($models) {

	return $models[$id];

});

$nodes = ActiveRecord\get_model('nodes');
```





## Requirements

The package requires PHP 5.5 or later and the [PDO extension](http://php.net/manual/en/intro.pdo.php).





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/):

```
$ composer require icanboogie/activerecord
```





### Cloning the repository

The package is [available on GitHub](https://github.com/ICanBoogie/ActiveRecord), its repository
can be cloned with the following command line:

	$ git clone https://github.com/ICanBoogie/ActiveRecord.git





## Documentation

The package is documented as part of the [ICanBoogie][] framework
[documentation](http://icanboogie.org/docs/). You can generate the documentation for the package and its dependencies with the `make doc` command. The documentation is generated in the `build/docs` directory. [ApiGen](http://apigen.org/) is required. The directory can later be cleaned with the `make clean` command.





## Testing

The test suite is ran with the `make test` command. [PHPUnit](https://phpunit.de/) and [Composer](http://getcomposer.org/) need to be globally available to run the suite. The command installs dependencies as required. The `make test-coverage` command runs test suite and also creates an HTML coverage report in "build/coverage". The directory can later be cleaned with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://img.shields.io/travis/ICanBoogie/ActiveRecord.svg)](https://travis-ci.org/ICanBoogie/ActiveRecord)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/ActiveRecord.svg)](https://coveralls.io/r/ICanBoogie/ActiveRecord)





## License

ICanBoogie/ActiveRecord is licensed under the New BSD License - See the [LICENSE](LICENSE) file for details.





[icanboogie/bind-activerecord]: https://github.com/ICanBoogie/bind-activerecord
[Connection]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.Connection.html
[ConnectionAlreadyEstablished]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ConnectionAlreadyEstablished.html
[ConnectionNotDefined]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ConnectionNotDefined.html
[ConnectionNotEstablished]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ConnectionNotEstablished.html
[ConnectionCollection]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ConnectionCollection.html
[CreatedAtProperty]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.CreatedAtProperty.html
[DateTime]: http://icanboogie.org/docs/class-ICanBoogie.DateTime.html
[DateTimeProperty]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.DateTimeProperty.html
[ICanBoogie]: http://icanboogie.org
[Model]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.Model.html
[ModelAlreadyInstantiated]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ModelAlreadyInstantiated.html
[ModelNotDefined]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ModelNotDefined.html
[ModelCollection]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ModelCollection.html
[PDO]: http://php.net/manual/en/book.pdo.php
[Query]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.Query.html
[RecordNotFound]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.RecordNotFound.html
[RelationNotDefined]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.RelationNotDefined.html
[RunTimeActiveRecordCache]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.RunTimeActiveRecordCache.html
[ScopeNotDefined]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.ScopeNotDefined.html
[StatementNotValid]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.StatementNotValid.html
[UnableToSetFetchMode]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.UnableToSetFetchMode.html
[UpdatedAtProperty]: http://icanboogie.org/docs/class-ICanBoogie.ActiveRecord.UpdatedAtProperty.html
