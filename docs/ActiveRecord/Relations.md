# Relations

Records relate to each other in four ways: a record class can extend another, a record can belong
to another, a record can have many others, and a record can have one other. The models of the
related records must be defined in the same config.

The examples use the records of [Getting started](../GettingStarted.md), and `$builder` is a
`ConfigBuilder` with these records already added.



## Extending another record

A record class can extend another record class, just like any PHP class. Each class has its own
table, and the child table shares the primary key of its parent table. When the child is queried,
the tables are joined. When it is saved, its values are split between the tables. The child also
uses the connection of its parent.

`Article` extends `Node`. Nodes have a title, and articles add a body and a date:

<!-- doc-test: excerpt -->
```php
<?php

class Node extends ActiveRecord
{
    #[Id, Serial]
    public int $nid;

    #[Character(80)]
    public string $title;

    // …
}

class Article extends Node
{
    // …

    #[Text]
    public string $body;

    #[Date]
    public string $date;

    // …
}
```

```php
<?php

namespace App;

/* @var $alice User */

$article = Article::from([
    'user_id' => $alice->id,
    'title' => "My article",
    'body' => "Testing",
    'date' => '2026-10-12',
])->save();

echo Article::query();
// SELECT `article`.*, `node`.* FROM `articles` `article` INNER JOIN `nodes` `node` USING(`nid`)
```

The parent record must be added to the config before the child, and its primary key must be a
single integer column. The model of the parent is available with `$model->parent`.



## Belongs to

A record can belong to another record. For instance, an article belongs to its author. The relation
is declared with the [BelongsTo][] attribute on an integer property, or with
`SchemaBuilder::belongs_to()`. The primary key of the associate must be a single column.

A getter is added to the record class, and returns the associate record, or `null` if the column is
`null`. Its name is the column name without its `_id` suffix: `user_id` adds a `user` getter. Use
`as` to choose another name, which you must do when the column doesn't end with `_id`, such as the
`nid` column of comments:

<!-- doc-test: excerpt -->
```php
<?php

/**
 * @property-read Article $article
 * @property-read User $user
 */
class Comment extends ActiveRecord
{
    // …

    #[BelongsTo(Article::class, as: 'article', on_delete: OnDelete::Cascade)]
    public int $nid;

    #[BelongsTo(User::class, on_delete: OnDelete::Cascade)]
    public int $user_id;

    // …
}
```

```php
<?php

namespace App;

/* @var $comment Comment */

echo "{$comment->user->username} commented on {$comment->article->title}.";
// bob commented on Hello world.
```



### Foreign key constraints

A _belongs to_ relation can also create a foreign key constraint when the table is created. This is
opt-in: specify what should happen to a record when the record it belongs to is deleted, using
`on_delete`. Above, comments are deleted with their article and their author, and articles are
deleted with their author.

The same option is available with `SchemaBuilder::belongs_to()`. The available actions are
`OnDelete::Cascade`, `OnDelete::SetNull`, `OnDelete::Restrict`, and `OnDelete::NoAction`. With
`OnDelete::SetNull`, the column must be nullable.

The following applies when a foreign key is created:

- The column gets the size and the signedness of the referenced primary key, which must be a single
  integer column.
- The referenced model must use the same connection.
- [ModelInstaller](ModelInstaller.md) creates the referenced tables first, and drops them last.
  Foreign keys that form a cycle between tables are not supported, a table referencing itself is
  fine.
- Foreign keys are enforced on the three engines. SQLite connections enable them with
  `PRAGMA foreign_keys = ON`, which SQLite doesn't do by default.
- The database applies the action on the table that has the foreign key. When a user is deleted,
  the `articles` rows of their articles are deleted, but not the `nodes` rows of the parent table.

```php
<?php

namespace App;

/* @var $alice User */

$alice->delete();

echo Article::query()->count; // 1, the article of bob
echo Comment::query()->count; // 0, the comment was on the article of alice
echo Node::query()->count;    // 2, the node of the deleted article remains
```



## Has many

A record can have many other records. For instance, a user has many articles. The relation is
declared with the [HasMany][] attribute on the record class, which is repeatable, or with the
`association_builder` parameter of `ConfigBuilder::add_record()`. The primary key of the owner must
be a single column.

A getter is added to the record class, and returns a [Query](Query.md) for the related records.
Its name is the plural of the alias of the related model, `articles` for `Article`. Use `as` to
choose another name.

`foreign_key` is the column of the related records that references the owner. When it's omitted,
it's resolved from the [BelongsTo][] column of the related record that references the owner, or one
of its ancestors. Articles reference users with `user_id`, and comments reference articles with
`nid`, so both can be omitted. A `BelongsTo` that references the owner itself is preferred over one
that references an ancestor. The configuration fails if no column references the owner, or if
several do, as with a record that references the same user as author and editor. Then, specify
`foreign_key`:

<!-- doc-test: excerpt -->
```php
<?php

/**
 * @property-read Query<Article> $articles
 */
#[HasMany(Article::class)]
class User extends ActiveRecord
{
    // …
}

/**
 * @property-read Query<Comment> $comments
 */
#[HasMany(Comment::class)]
class Article extends Node
{
    // …
}
```

```php
<?php

namespace App;

/* @var $alice User */

foreach ($alice->articles as $article) {
    echo "{$alice->username} wrote {$article->title}, ";
    echo "which has {$article->comments->count} comment(s).\n";
}

$latest_comments = $article->comments->order('-id')->take(5)->all;
```

The association builder completes the relations declared by the attributes of the record. Defining a
relation twice, with the same options, throws an [InvalidAssociation][] exception:

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\Config\AssociationBuilder;

/* @var $builder \ICanBoogie\ActiveRecord\ConfigBuilder */

$builder->add_record(
    record_class: User::class,
    association_builder: fn(AssociationBuilder $association) => $association
        ->has_many(Article::class, as: 'posts'),
);
```



### Has many through

A relation can go through a pivot record, which belongs to both records. An article has many
commenters, the users who commented on it, through comments:

<!-- doc-test: excerpt -->
```php
<?php

/**
 * @property-read Query<User> $commenters
 */
#[HasMany(User::class, through: Comment::class, as: 'commenters')]
class Article extends Node
{
    // …
}
```

```php
<?php

namespace App;

/* @var $article Article */

foreach ($article->commenters as $user) {
    echo "{$user->username} commented on {$article->title}.\n";
}
```

Both columns are on the pivot, and resolved from its [BelongsTo][] columns when the config is built,
as for a relation without pivot: the column that references the owner, or one of its ancestors, and
the column that references the related record, or one of its ancestors. Comments reference articles
with `nid`, and users with `user_id`. With `through`, `foreign_key` is the column of the pivot that
references the owner. When the pivot references the owner and the related record with the same
class, as friendships between users do, specify it, and the other column references the related
record:

<!-- doc-test: skip: the example records of the documentation have no such pivot -->
```php
<?php

/**
 * @property-read Query<User> $friends
 */
#[HasMany(User::class, foreign_key: 'user_id', through: Friendship::class, as: 'friends')]
class User extends ActiveRecord
{
    // …
}

class Friendship extends ActiveRecord
{
    #[BelongsTo(User::class)]
    public int $user_id;

    #[BelongsTo(User::class)]
    public int $friend_id;
}
```



## Has one

A record can have one other record. For instance, an employee has one badge. The relation is
declared with the [HasOne][] attribute on the record class, which is repeatable, or with
`AssociationBuilder::has_one()`. It takes the same options as [Has many](#has-many), and its
`foreign_key` is resolved the same way, directly or through a pivot record.

A getter is added to the record class, and returns the related record, or `null` if there's none.
Its name is the alias of the related model, `badge` for `Badge`. Use `as` to choose another name.

The getter returns the first related record. That there is at most one is up to the schema: make
the column that references the owner unique, or, through a pivot, make it the primary key of the
pivot. Below, a badge references its employee with a unique column, and a desk assignment has the
employee as primary key:

<!-- doc-test: skip: the example records of the documentation have no one-to-one relation -->
```php
<?php

/**
 * @property-read Badge|null $badge
 * @property-read Desk|null $desk
 */
#[HasOne(Badge::class)]
#[HasOne(Desk::class, through: DeskAssignment::class)]
class Employee extends ActiveRecord
{
    #[Id, Serial]
    public int $employee_id;
}

class Badge extends ActiveRecord
{
    #[Id, Serial]
    public int $badge_id;

    #[BelongsTo(Employee::class, unique: true)]
    public int $employee_id;
}

class DeskAssignment extends ActiveRecord
{
    #[Id, BelongsTo(Employee::class)]
    public int $employee_id;

    #[BelongsTo(Desk::class)]
    public int $desk_id;
}
```

<!-- doc-test: skip: the example records of the documentation have no one-to-one relation -->
```php
<?php

/* @var $employee Employee */

echo $employee->badge?->badge_id ?? "No badge";
echo $employee->desk?->desk_id ?? "No desk";
```

Like a belongs-to getter, a has-one getter runs a query each time it's accessed, nothing is cached,
unless the relation was [preloaded](#preloading-relations). Keep the record in a variable to use it
more than once.



## Preloading relations

Reading a relation of each record of a list runs a query per record. For 100 comments, reading
`$comment->user` runs 100 queries on top of the one that fetched the comments. To run one query
instead, name the relations in `Query::includes()`, the records that `all()` fetches come with
them loaded:

```php
<?php

namespace App;

foreach (Comment::query()->includes('user', 'article')->order('-id') as $comment) {
    echo "{$comment->user->username} commented on {$comment->article->title}.\n"; // no query
}
```

The relations are loaded after the records are fetched, with one query per relation, whatever the
number of records. That also is the case when iterating, once per batch, and with the `all`
property. Comments of the same user share the same `User` instance.

- Belongs-to, has-one and has-many relations can be preloaded, the last two only when they aren't
  `through` a pivot. `includes()` throws a [RelationNotPreloadable][] exception for the other
  ones, instead of leaving you with the queries that you wanted to avoid, and a
  [RelationNotDefined][] exception for a name that isn't a relation of the model or of its parents.
  The relations are named one level at a time: `'user.country'` isn't supported.
- A has-one relation without a related record is loaded as `null`.
- A has-many getter returns a `Query`, which can't hold records. The preloaded records are read
  with the `_loaded` getter, see [below](#reading-a-preloaded-has-many).
- A belongs-to relation with a key that doesn't match any record isn't loaded, reading it throws
  [RecordNotFound][], as it does when it isn't preloaded.
- Assigning another value to the foreign key invalidates what was loaded, the getter runs a query.
  A cloned record doesn't keep what was loaded either.
- `one()`, `count()` and the other methods that don't fetch a list ignore the relations.
  `all()` throws a `LogicException` if they're included and the fetch mode doesn't create records,
  e.g. with `select()`, since there is no record to attach them to.

### Reading a preloaded has-many

`$article->comments` is a `Query` that you can chain on, so it queries each time it's read,
preloaded or not. A has-many relation has a second getter, named after the first with a `_loaded`
suffix, that returns the records as a list: the ones that were preloaded, or the ones that a query
fetches if the relation wasn't preloaded for the record. That is also what happens with a relation
that is `through` a pivot, which can't be preloaded.

<!-- doc-test: skip: needs saved records -->
```php
<?php

namespace App;

foreach (Article::query()->includes('comments')->all as $article) {
    echo count($article->comments_loaded), " comments\n"; // no query
}
```

An article without comments is loaded with an empty list. The list is a snapshot: comments added
after the preload are not in it, and assigning another value to the primary key of the owner
invalidates it.

A relation can also be preloaded for records you already have, with `Relation::preload()`. Records
that already have the relation loaded are skipped, and no query is run if there's none left:

<!-- doc-test: skip: needs saved records -->
```php
<?php

namespace App;

/* @var $comments Comment[] */

Comment::model()->relations->resolve('user')->preload($comments);
```



[BelongsTo]: ../../lib/ActiveRecord/Schema/BelongsTo.php
[HasMany]:   ../../lib/ActiveRecord/Schema/HasMany.php
[HasOne]:    ../../lib/ActiveRecord/Schema/HasOne.php
[InvalidAssociation]: ../../lib/ActiveRecord/Config/InvalidAssociation.php
[RecordNotFound]: ../../lib/ActiveRecord/RecordNotFound.php
[RelationNotDefined]: ../../lib/ActiveRecord/RelationNotDefined.php
[RelationNotPreloadable]: ../../lib/ActiveRecord/RelationNotPreloadable.php
