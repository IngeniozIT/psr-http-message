# PSR-3 : Logger

An implementation of the PSR-3 : LoggerInterface.

[PSR-3 documentation](https://www.php-fig.org/psr/psr-3/)

[![Packagist Version](https://img.shields.io/packagist/v/ingenioz-it/psr3.svg)](https://packagist.org/packages/ingenioz-it/psr3)
![PHP from Packagist](https://img.shields.io/packagist/php-v/ingenioz-it/psr3.svg)
![Packagist](https://img.shields.io/packagist/l/ingenioz-it/psr3.svg)

Code quality :

[![Build Status](https://travis-ci.com/IngeniozIT/psr3-logger.svg?branch=master)](https://travis-ci.com/IngeniozIT/psr3-logger)
[![Code Coverage](https://codecov.io/gh/IngeniozIT/psr3-logger/branch/master/graph/badge.svg)](https://codecov.io/gh/IngeniozIT/psr3-logger)

# Installation

```sh
composer require ingenioz-it/psr3
```

# Usage

To create a `Logger` instance :

```php
use IngeniozIT\Psr3\Logger;

$logger = new Logger($logPath);
```

All logs will be stored in the file `$logPath`.

To log something, use :

```php
$logger->debug($message); // Detailed debug information.
$logger->info($message); // Interesting events.
$logger->notice($message); // Normal but significant events.
$logger->warning($message); // Exceptional occurrences that are not errors.
$logger->error($message); // Runtime errors that do not require immediate action but should typically be logged and monitored.
$logger->critical($message); // Critical conditions.
$logger->alert($message); // Action must be taken immediately.
$logger->emergency($message); // System is unusable.
```
