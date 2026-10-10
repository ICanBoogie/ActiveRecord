# Active Record

[![Release](https://img.shields.io/packagist/v/ICanBoogie/activerecord.svg)](https://packagist.org/packages/icanboogie/activerecord)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/ActiveRecord.svg)](https://coveralls.io/r/ICanBoogie/ActiveRecord)
[![Downloads](https://img.shields.io/packagist/dt/icanboogie/activerecord.svg)](https://packagist.org/packages/icanboogie/activerecord)

__Connections__, __models__ and __active records__ are the foundations of everything that concerns
database access and management. They are used to establish database connections, manage tables and
their relationships, as well as manage the records of these tables. Leveraging OOP, models and
active records are classes whose properties, getters/setters, and behavior can be inherited in a
business logic.

Using the __query interface__, you won't have to write raw SQL, manage table relationships, or worry
about injection.

Finally, using __registries__ you can define all your connections and models in a single place.
Connections are established and models are instantiated on demand, so feel free to define hundreds
of them.



## Requirements

- PHP 8.4+
- The `pdo` extension, with the driver of your database: SQLite, MySQL, or PostgreSQL.



## Installation

```shell
composer require icanboogie/activerecord
```

To use the package with the [ICanBoogie][] framework, install [icanboogie/bind-activerecord][]
instead.



## Documentation

- [Getting started](docs/GettingStarted.md)
- [Configuration](docs/Configuration.md)
- [Connections](docs/ActiveRecord/Connection.md)
- [Schema](docs/ActiveRecord/Schema.md)
- [Relations](docs/ActiveRecord/Relations.md)
- [Models](docs/ActiveRecord/Model.md)
- [Installing models](docs/ActiveRecord/ModelInstaller.md)
- [Active records](docs/ActiveRecord.md)
- [The query interface](docs/ActiveRecord/Query.md)
- [Exceptions](docs/ActiveRecord/Exception.md)
- [Database engines](docs/Engines.md)

See [CHANGELOG](CHANGELOG.md) for the changes between versions, and how to upgrade.



### Acknowledgments

The implementation of the query interface is vastly inspired by
[Ruby On Rails' Active Record Query Interface](https://guides.rubyonrails.org/active_record_querying.html).



----------



## Continuous Integration

The project is continuously tested by [GitHub Actions](https://github.com/ICanBoogie/ActiveRecord/actions).

[![Tests](https://github.com/ICanBoogie/ActiveRecord/actions/workflows/test.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/ActiveRecord/actions/workflows/test.yml)
[![Static Analysis](https://github.com/ICanBoogie/ActiveRecord/actions/workflows/static-analysis.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/ActiveRecord/actions/workflows/static-analysis.yml)
[![Code Style](https://github.com/ICanBoogie/ActiveRecord/actions/workflows/code-style.yml/badge.svg?branch=7.0)](https://github.com/ICanBoogie/ActiveRecord/actions/workflows/code-style.yml)



## Code of Conduct

This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md). By participating in
this project and its community, you're expected to uphold this code.



## Contributing

See [CONTRIBUTING](CONTRIBUTING.md) for details.



[ICanBoogie]:                   https://icanboogie.org
[icanboogie/bind-activerecord]: https://github.com/ICanBoogie/bind-activerecord
