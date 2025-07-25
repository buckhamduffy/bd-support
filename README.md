# BuckhamDuffy Support

[![Latest Version on Packagist](https://img.shields.io/packagist/v/buckhamduffy/bd-support.svg?style=flat-square)](https://packagist.org/packages/buckhamduffy/bd-support)
[![Total Downloads](https://img.shields.io/packagist/dt/buckhamduffy/bd-support.svg?style=flat-square)](https://packagist.org/packages/buckhamduffy/bd-support)

## Installation

You can install the package via composer:

```bash
composer require buckhamduffy/bd-support
```

### Health

##### app/Console/Kernel.php
```php
protected function schedule(Schedule $schedule): void {
	$schedule->command('synapse:send-healthcheck-email')->everyTenMinutes();
}
```

##### /.synapse/info
information for /.synapse/info is pulled from the `bd-support.php` config file.
```env
APP_ENV=production
COMMIT_SHA=6e9fa3fc2095d7a74732c2dba6295b61fd8c46db
GIT_BRANCH=master
BRANCH_NAME=master
SENTRY_RELEASE=v1.0.0
````

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Aaron Florey](https://github.com/BuckhamDuffy)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
