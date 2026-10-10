# Getting started

This page walks through a minimal setup: a few records, a SQLite database in memory, and some
queries. The records defined here are used by the examples of the other pages:

- [Configuration](Configuration.md)
- [Connections](ActiveRecord/Connection.md)
- [Schema](ActiveRecord/Schema.md)
- [Relations](ActiveRecord/Relations.md)
- [Models](ActiveRecord/Model.md)
- [Installing models](ActiveRecord/ModelInstaller.md)
- [Active records](ActiveRecord.md)
- [The query interface](ActiveRecord/Query.md)
- [Exceptions](ActiveRecord/Exception.md)
- [Database engines](Engines.md)



## Binding the prototype methods

`ActiveRecord::validate()` is a prototype method, the application binds it. Unless you use
[icanboogie/bind-activerecord][], which does it for you, bind it before saving records:

<!-- doc-test: setup -->
```php
<?php

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Validate\ValidateActiveRecord;
use ICanBoogie\Prototype;

Prototype::bind(
    new Prototype\ConfigBuilder()
        ->bind(ActiveRecord::class, 'validate', function (ActiveRecord $record) {
            static $validate;

            $validate ??= new ValidateActiveRecord();

            return $validate($record);
        })
        ->build()
);
```



## Defining records

An active record is a class extending `ActiveRecord`. Its schema is declared with attributes on its
properties, see [Schema](ActiveRecord/Schema.md) for the available columns, and
[Relations](ActiveRecord/Relations.md) for the relations between records.

The examples of the documentation use the following records: users write articles, which are a
kind of node, and comment on them.

<!-- doc-test: setup -->
```php
<?php

namespace App;

use ICanBoogie\ActiveRecord;
use ICanBoogie\ActiveRecord\Query;
use ICanBoogie\ActiveRecord\Schema\BelongsTo;
use ICanBoogie\ActiveRecord\Schema\Boolean;
use ICanBoogie\ActiveRecord\Schema\Character;
use ICanBoogie\ActiveRecord\Schema\Date;
use ICanBoogie\ActiveRecord\Schema\HasMany;
use ICanBoogie\ActiveRecord\Schema\Id;
use ICanBoogie\ActiveRecord\Schema\Integer;
use ICanBoogie\ActiveRecord\Schema\OnDelete;
use ICanBoogie\ActiveRecord\Schema\Serial;
use ICanBoogie\ActiveRecord\Schema\Text;

/**
 * @property-read Query<Article> $articles
 */
#[HasMany(Article::class, foreign_key: 'user_id')]
class User extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[Character(32, unique: true)]
    public string $username;

    #[Character(unique: true)]
    public string $email;

    public function create_validation_rules(): array
    {
        return [
            'username' => 'required|max-length:32|unique',
            'email' => 'required|email|unique',
        ];
    }
}

class Node extends ActiveRecord
{
    #[Id, Serial]
    public int $nid;

    #[Character(80)]
    public string $title;

    #[Boolean(default: true)]
    public bool $is_online = true;
}

/**
 * @property-read Query<Comment> $comments
 * @property-read Query<User> $commenters
 */
#[HasMany(Comment::class)]
#[HasMany(User::class, through: Comment::class, as: 'commenters')]
class Article extends Node
{
    #[BelongsTo(User::class, on_delete: OnDelete::Cascade)]
    public int $user_id;

    #[Text]
    public string $body;

    #[Date]
    public string $date;

    #[Integer(default: 0)]
    public int $views = 0;
}

/**
 * @property-read Article $article
 * @property-read User $user
 */
class Comment extends ActiveRecord
{
    #[Id, Serial]
    public int $id;

    #[BelongsTo(Article::class, as: 'article', on_delete: OnDelete::Cascade)]
    public int $nid;

    #[BelongsTo(User::class, on_delete: OnDelete::Cascade)]
    public int $user_id;

    #[Text]
    public string $body;
}
```



## Configuring connections and models

`ConfigBuilder` collects the connections and the records, and builds a `Config`. A
`ConnectionRegistry` establishes the connections on demand, and a `ModelRegistry` instantiates the
models on demand. `ModelInstaller` creates the tables, in the order required by their
dependencies. See [Configuration](Configuration.md) for the details.

<!-- doc-test: setup -->
```php
<?php

namespace App;

use ICanBoogie\ActiveRecord\ConfigBuilder;
use ICanBoogie\ActiveRecord\ConnectionRegistry;
use ICanBoogie\ActiveRecord\ModelInstaller;
use ICanBoogie\ActiveRecord\ModelRegistry;
use ICanBoogie\ActiveRecord\StaticModelProvider;

$config = new ConfigBuilder()
    ->use_attributes()
    ->add_connection('primary', 'sqlite::memory:')
    ->add_record(User::class)
    ->add_record(Node::class)
    ->add_record(Article::class)
    ->add_record(Comment::class)
    ->build();

$connections = new ConnectionRegistry($config->connections);
$models = new ModelRegistry($connections, $config->models);

new ModelInstaller($models)->install();

// Active records retrieve their model with the static model provider.
StaticModelProvider::set(fn() => $models);
```



## Saving and querying records

<!-- doc-test: setup -->
```php
<?php

namespace App;

$alice = User::from([ 'username' => "alice", 'email' => "alice@example.com" ])->save();
$bob = User::from([ 'username' => "bob", 'email' => "bob@example.com" ])->save();

$hello = Article::from([
    'user_id' => $alice->id,
    'title' => "Hello world",
    'body' => "My first article.",
    'date' => '2026-10-10',
    'views' => 42,
])->save();

Article::from([
    'user_id' => $bob->id,
    'title' => "Work in progress",
    'body' => "Not ready yet.",
    'date' => '2026-10-11',
    'is_online' => false,
])->save();

Comment::from([ 'nid' => $hello->nid, 'user_id' => $bob->id, 'body' => "Great article!" ])->save();

foreach ($alice->articles as $article) {
    echo "{$article->title} by {$article->user->username}\n"; // Hello world by alice

    foreach ($article->comments as $comment) {
        echo "{$comment->user->username}: {$comment->body}\n"; // bob: Great article!
    }
}

echo Article::where([ 'is_online' => true ])->count, "\n"; // 1
```



[icanboogie/bind-activerecord]: https://github.com/ICanBoogie/bind-activerecord
