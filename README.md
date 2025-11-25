# Package "ctw/ctw-middleware-phpconfig"

[![Latest Stable Version](https://poser.pugx.org/ctw/ctw-middleware-phpconfig/v/stable)](https://packagist.org/packages/ctw/ctw-middleware-phpconfig)
[![GitHub Actions](https://github.com/jonathanmaron/ctw-middleware-phpconfig/actions/workflows/tests.yml/badge.svg)](https://github.com/jonathanmaron/ctw-middleware-phpconfig/actions/workflows/tests.yml)
[![Scrutinizer Build](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-phpconfig/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-phpconfig/build-status/master)
[![Scrutinizer Quality](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-phpconfig/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-phpconfig/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-phpconfig/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-phpconfig/?branch=master)

PSR-15 middleware that sets PHP configuration options at runtime from application configuration files.

## Introduction

### Why This Library Exists

PHP applications often require specific `php.ini` settings that differ from server defaults. While these can be set in the server's `php.ini` or `.htaccess` files, managing settings at the application level provides several advantages:

- **Version control**: Configuration settings travel with your application code
- **Environment flexibility**: Different settings for development, staging, and production
- **Shared hosting compatibility**: Works even when `php.ini` modifications are restricted
- **Centralized management**: All application configuration in one place
- **Runtime adaptability**: Settings can be computed or loaded from external sources

This middleware applies PHP configuration settings early in the request lifecycle, ensuring all subsequent code operates with the correct settings.

### Problems This Library Solves

1. **Server dependency**: Relying on server administrators to configure `php.ini` correctly
2. **Configuration drift**: Settings differ between environments without version control
3. **Shared hosting limits**: Many hosts restrict `php.ini` modifications
4. **Scattered configuration**: PHP settings in `.htaccess`, `php.ini`, and code are hard to track
5. **Environment-specific needs**: Development needs different settings than production

### Where to Use This Library

- **Shared hosting environments**: Set configuration when server access is limited
- **Containerized applications**: Configure PHP settings as part of application bootstrap
- **Multi-environment deployments**: Different settings per environment via config files
- **Framework applications**: Integrate PHP configuration with Mezzio/Laminas config system
- **Development overrides**: Enable error display, increase memory limits during development

### Design Goals

1. **Configuration-driven**: Settings come from application config arrays, not hardcoded
2. **Type normalization**: Converts booleans to `On`/`Off`, integers to strings automatically
3. **Fail-fast validation**: Throws exceptions for invalid or unchangeable options
4. **Early execution**: Apply settings before other middleware processes requests
5. **Transparent operation**: No modification to request or response

## Requirements

- PHP 8.3 or higher
- ctw/ctw-middleware ^4.0

## Installation

Install by adding the package as a [Composer](https://getcomposer.org) requirement:

```bash
composer require ctw/ctw-middleware-phpconfig
```

## Usage Examples

### Basic Pipeline Registration (Mezzio)

```php
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddleware;

// In config/pipeline.php - place early in the pipeline
$app->pipe(PhpConfigMiddleware::class);
```

### ConfigProvider Registration

```php
// config/config.php
return [
    // ...
    \Ctw\Middleware\PhpConfigMiddleware\ConfigProvider::class,
];
```

### Configuration File

```php
// config/autoload/php-config.global.php
return [
    'php_config' => [
        'display_errors'         => false,
        'error_reporting'        => E_ALL,
        'max_execution_time'     => 30,
        'memory_limit'           => '256M',
        'post_max_size'          => '64M',
        'upload_max_filesize'    => '64M',
        'date.timezone'          => 'UTC',
        'session.cookie_secure'  => true,
        'session.cookie_httponly'=> true,
    ],
];
```

### Development Overrides

```php
// config/autoload/php-config.local.php (git-ignored)
return [
    'php_config' => [
        'display_errors'    => true,
        'error_reporting'   => E_ALL,
        'memory_limit'      => '512M',
        'max_execution_time'=> 0, // No limit in development
    ],
];
```

### Type Conversion

The middleware automatically converts values to the correct format for `ini_set()`:

| PHP Type | Input | Output |
|----------|-------|--------|
| `bool` | `true` | `'On'` |
| `bool` | `false` | `'Off'` |
| `int` | `256` | `'256'` |
| `string` | `'256M'` | `'256M'` |
| `null` | `null` | `''` |

### Common Configuration Options

| Option | Description | Example |
|--------|-------------|---------|
| `display_errors` | Show errors in output | `false` (production) |
| `error_reporting` | Error level bitmask | `E_ALL` |
| `memory_limit` | Maximum memory usage | `'256M'` |
| `max_execution_time` | Script timeout in seconds | `30` |
| `upload_max_filesize` | Maximum upload file size | `'64M'` |
| `post_max_size` | Maximum POST data size | `'64M'` |
| `date.timezone` | Default timezone | `'UTC'` |
| `session.cookie_secure` | HTTPS-only cookies | `true` |
| `session.cookie_httponly` | HTTP-only cookies | `true` |

### Error Handling

If a configuration option cannot be set (due to PHP restrictions or invalid option names), the middleware throws an `UnexpectedValueException`:

```php
use Ctw\Middleware\PhpConfigMiddleware\Exception\UnexpectedValueException;

try {
    $app->run();
} catch (UnexpectedValueException $e) {
    // Handle configuration error
    error_log($e->getMessage());
}
```
