# Relations

Records relate to each other in three ways: a record class can extend another, a record can belong
to another, and a record can have many others. The models of the related records must be defined in
the same config.

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

The same relation with the association builder:

```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\Config\AssociationBuilder;

/* @var $builder \ICanBoogie\ActiveRecord\ConfigBuilder */

$builder->add_record(
    record_class: User::class,
    association_builder: fn(AssociationBuilder $association) => $association
        ->has_many(Article::class),
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



[BelongsTo]: ../../lib/ActiveRecord/Schema/BelongsTo.php
[HasMany]:   ../../lib/ActiveRecord/Schema/HasMany.php
