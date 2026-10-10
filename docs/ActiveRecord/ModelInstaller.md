# Installing models

Installing a model creates its table. Uninstalling it drops the table.

The examples use the records of [Getting started](../GettingStarted.md).



## Installing a single model

```php
<?php

namespace App;

$model = Comment::model();

if ($model->is_installed()) {
    $model->uninstall();
}

$model->install();
```

`is_installed()` only checks that the table exists, not that its schema is up to date.

`install()` doesn't install the parent model, or the models referenced by foreign keys. To install
models in the right order, use a model installer.



## Installing all the models

A [ModelInstaller][] installs and uninstalls the models of a [ModelRegistry][], or any other
[ModelIterator][]. A model is installed after its parent and the models referenced by its
[foreign keys](Relations.md#foreign-key-constraints), and uninstalled before them. Models that are
already installed are left as they are.

```php
<?php

use ICanBoogie\ActiveRecord\ModelInstaller;

/* @var $models \ICanBoogie\ActiveRecord\ModelRegistry */

$installer = new ModelInstaller($models);
$installer->install();
var_dump($installer->is_installed()); // [ User::class => true, Node::class => true, … ]
$installer->uninstall();
var_dump($installer->is_installed()); // [ User::class => false, Node::class => false, … ]
```

A `LogicException` is thrown if the dependencies of the models form a cycle.



## Following the installation

`install()` throws the first failure. To follow the installation, or to keep going when a model
fails, pass an [InstallProgress][] implementation. It is notified of each model, in install order:

- `already_installed()`: The table already exists, nothing was done.
- `installing()`: The model is about to be installed, followed by `installed()` or `failed()`.
- `installed()`: The table was created.
- `failed()`: The table could not be created. Throw to abort the installation, return to
  continue with the models that don't depend on this one.
- `skipped()`: The model depends on a model that failed or was skipped, it wasn't installed. The
  dependency is the parent model, or a model referenced by a foreign key.

```php
<?php

use ICanBoogie\ActiveRecord\InstallProgress;
use ICanBoogie\ActiveRecord\ModelInstaller;

/* @var $models \ICanBoogie\ActiveRecord\ModelRegistry */

new ModelInstaller($models)->install(new class () implements InstallProgress {
    public function already_installed(string $activerecord_class): void
    {
    }

    public function installing(string $activerecord_class): void
    {
    }

    public function installed(string $activerecord_class): void
    {
        echo "$activerecord_class: installed\n";
    }

    public function failed(string $activerecord_class, Throwable $error): void
    {
        echo "$activerecord_class: {$error->getMessage()}\n";
    }

    public function skipped(string $activerecord_class, string $dependency): void
    {
        echo "$activerecord_class: skipped, depends on $dependency\n";
    }
});
```

The `activerecord:install` command of [icanboogie/bind-activerecord][] uses it to report each
model as installed, already installed, failed, or skipped.



[InstallProgress]:              ../../lib/ActiveRecord/InstallProgress.php
[ModelInstaller]:               ../../lib/ActiveRecord/ModelInstaller.php
[ModelIterator]:                ../../lib/ActiveRecord/ModelIterator.php
[ModelRegistry]:                ../../lib/ActiveRecord/ModelRegistry.php
[icanboogie/bind-activerecord]: https://github.com/ICanBoogie/bind-activerecord
