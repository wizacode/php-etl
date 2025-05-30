# Wizaplace PHP ETL (WP-ETL)

[![License](https://poser.pugx.org/wizaplace/php-etl/license)](https://packagist.org/packages/wizaplace/php-etl)
[![CircleCI](https://circleci.com/gh/wizacode/php-etl/tree/master.svg?style=svg)](https://circleci.com/gh/wizaplace/php-etl/tree/master)
[![Version](https://img.shields.io/github/v/release/wizaplace/php-etl)](https://circleci.com/gh/wizaplace/php-etl/tree/master)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://GitHub.com/wizaplace/php-etl/graphs/commit-activity)
[![Ask Me Anything !](https://img.shields.io/badge/Ask%20me-anything-1abc9c.svg)](https://GitHub.com/wizaplace/php-etl)
![PHP Version](https://img.shields.io/packagist/php-v/wizaplace/php-etl)

Extract, Transform and Load data using PHP.
This library provides classes and a workflow to allow you to extract data from various sources (CSV, DB...), one or many, then transform them before saving them in another format.

You can also easily add your custom classes (Extractors, Transformers and Loaders).

![ETL](docs/img/etl.svg)

## Versions and compatibility
* To benefit from the latest features and if you use PHP 8.1 and above: use the 2.3 version (and above) of the library.
* If you use older versions of PHP: 7.4 or 8.0, use the 2.2 version of the library.
* If you use older versions of PHP: 7.2 <= PHP <= 7.4, use the legacy 1.3.x version.

## Changelog

See the changelog [here](changelog.MD)

## Installation

In your application's folder, run:

```shell
composer require wizaplace/php-etl
```

## Example :light_rail:

In the example below, we will extract data from a csv file, trim white spaces from the name and email columns and then insert the values into the _users_ table:

```php
use Wizaplace\Etl\Etl;
use Wizaplace\Etl\Extractors\Csv;
use Wizaplace\Etl\Transformers\Trim;
use Wizaplace\Etl\Loaders\Insert;
use Wizaplace\Etl\Database\Manager;
use Wizaplace\Etl\Database\ConnectionFactory;

// Get your database settings :
$config = [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'port'      => '3306',
    'database'  => 'myDatabase',
    'username'  => 'foo',
    'password'  => 'bar',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
];

// Instanciate all the components (manually or automatically with DI)
$manager = new Manager(new ConnectionFactory());
$manager->addConnection($config);
$etl = new Etl();
$extractor = new Csv();
$transformer = new Trim();
$loader = new Insert($manager);

$etl->extract($extractor, '/path/to/users.csv')
    ->transform(
        $transformer,
        [Step::COLUMNS => ['name', 'email']]
    )
    ->load($loader, 'users')
    ->run();
```

The library is fully compatible with any PHP project.
For instance, with Symfony, you can fully benefit from the autowiring. On the following example, you enable it on the
main ETL object, with the _shared_ parameter to _false_ in order to have the possibility to get
different instance of the ETL (optional).

_services.yaml_

```yaml
    Wizaplace\Etl\Etl:
        shared: false
```

## How to contribute?

Below are some (not exhaustive) welcomed features for a 3.x version.
* Dropping support of older PHP versions.
* Type-hinting, rector...
* Putting PHPCS back on the project.
* Updating PHPUnit
* Improve code in order to remove some PHPStan exclusions.

## Documentation :notebook:

The documentation is available in a subfolder of the repo, [here](docs/README.md).

## License

WP-ETL is licensed under the [MIT license](http://opensource.org/licenses/MIT).

## Origin of the project

This project is a fork and an improvement of the [marquine/php-etl](https://github.com/leomarquine/php-etl) project by [Leonardo Marquine](https://github.com/leomarquine/php-etl).
