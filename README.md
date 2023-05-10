# PSR Message

An implementation of the [PSR-7](https://www.php-fig.org/psr/psr-7/) Http Message interfaces and the [PSR-17](https://www.php-fig.org/psr/psr-17/) Http Factories interfaces that focuses on code quality.

## About

| Info                | Value                                                                                                                                                                                                                                                                                                                                      |
|---------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Latest release      | [![Packagist Version](https://img.shields.io/packagist/v/ingenioz-it/http-message)](https://packagist.org/packages/ingenioz-it/http-message)                                                                                                                                                                                               |
| Requires            | ![PHP from Packagist](https://img.shields.io/packagist/php-v/ingenioz-it/http-message.svg)                                                                                                                                                                                                                                                 |
| License             | ![Packagist](https://img.shields.io/packagist/l/ingenioz-it/http-message)                                                                                                                                                                                                                                                                  |
| Unit tests          | [![tests](https://github.com/IngeniozIT/psr-http-message/actions/workflows/1-tests.yml/badge.svg)](https://github.com/IngeniozIT/psr-http-message/actions/workflows/1-tests.yml)                                                                                                                                                           |
| Code coverage       | [![Code Coverage](https://codecov.io/gh/IngeniozIT/psr-http-message/branch/master/graph/badge.svg)](https://codecov.io/gh/IngeniozIT/psr-http-message)                                                                                                                                                                                     |
| Code quality        | [![code-quality](https://github.com/IngeniozIT/psr-http-message/actions/workflows/2-code-quality.yml/badge.svg)](https://github.com/IngeniozIT/psr-http-message/actions/workflows/2-code-quality.yml)                                                                                                                                      |
| Quality tested with | [phpunit](https://github.com/sebastianbergmann/phpunit), [phan](https://github.com/phan/phan), [psalm](https://github.com/vimeo/psalm), [phpcs](https://github.com/squizlabs/PHP_CodeSniffer), [phpstan](https://github.com/phpstan/phpstan), [phpmd](https://github.com/phpmd/phpmd), [infection](https://github.com/infection/infection) |

## Installation

```sh
composer require ingenioz-it/http-message
```

## Extra feature

This implementation strictly follows the PSR-7 and PSR-17 specifications, but it also provides one useful extra feature: the ability to create a `ServerRequest` from the global variables.

```php
use IngeniozIT\Http\Message\ServerRequestFactory;

$factory = new ServerRequestFactory(/* ... */);
$serverRequest = $factory->fromGlobals($GLOBALS);
```

## Full documentation

You can list all the available features by running

```sh
composer testdox
```
