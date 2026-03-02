# Multilingual package for Laravel with translation support

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rivalex/lingua.svg)](https://packagist.org/packages/rivalex/lingua)
[![codecov](https://codecov.io/github/rivalex/lingua/branch/main/graph/badge.svg?token=9RKRB8AYD6)](https://codecov.io/github/rivalex/lingua)
[![run-tests](https://github.com/rivalex/lingua/actions/workflows/run-tests.yml/badge.svg)](https://github.com/rivalex/lingua/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/rivalex/lingua/fix-php-code-style-issues.yml?branch=main&label=code%20style)](https://github.com/rivalex/lingua/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rivalex/lingua.svg?style)](https://packagist.org/packages/rivalex/lingua)

Lingua is a Laravel package for complete translation management: database, locale files, Livewire UI for languages and phrases, locale middleware, artisan synchronization commands, and UI tools for language selection. Ideal for modern multilingual applications.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/lingua.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/lingua)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require rivalex/lingua:dev-main
```

Publish the required migrations with:

```bash
php artisan vendor:publish --tag="lingua-migrations"
```

Run the migrations with:
```bash
php artisan migrate
```

You can optionally publish the config file with:

```bash
php artisan vendor:publish --tag="lingua-config"
```

This is the contents of the published config file:

```php
return [
    'lang_dir' => 'resources/lang',
    'default_locale' => config('app.locale', 'en'),
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="lingua-views"
```

## Usage

```php
$translate = new rivalex\Lingua();
echo $translate->echoPhrase('Hello, rivalex!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alessandro Rivolta](https://github.com/rivalex)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
