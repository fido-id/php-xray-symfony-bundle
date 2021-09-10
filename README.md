# fido-id/php-xray-symfony-bundle
[![codecov](https://codecov.io/gh/fido-id/php-xray-symfony-bundle/branch/main/graph/badge.svg?token=h04cGNVGvx)](https://codecov.io/gh/fido-id/php-xray-symfony-bundle)
[![PHP Version](https://img.shields.io/badge/php->=8.0-blue)](https://www.php.net/releases/8.0/en.php)
[![Release](https://github.com/fido-id/php-xray-symfony-bundle/actions/workflows/release.yaml/badge.svg)](https://github.com/fido-id/php-xray-symfony-bundle/actions/workflows/release.yaml)


A Symfony Bundle for [AWS X-Ray](https://docs.aws.amazon.com/xray/latest/devguide/aws-xray.html) for PHP 8.x
[LICENSE](LICENSE.md)
[CHANGELOG](CHANGELOG-0.x.md)

## Installation

To use this package, use [Composer](https://getcomposer.org/):

* From CLI: `composer require fido-id/php-xray-symfony-bundle`
* Or, directly in your `composer.json`:

```json
{
  "require": {
    "fido-id/php-xray-symfony-bundle": "^0.1.0"
  }
}
```

## Usage

Add the bundle inside `config/bundles.php` file, inside your Symfony Project
```php
<?php
return [
    ...
    Fido\PHPXRayBundle\FidoPHPXrayBundle::Class => ['all' => true],
];
```

Add the configuration inside `config/packages/xray.yaml`
```yaml
fido_php_xray:
    segment_name: 'your_project_name'
```

Inject the service for example in `config/services/controller.yaml`
```yaml
services:
  _defaults:
    autowire: false
    autoconfigure: true
    public: true
    bind:
      $segment: '@Fido\PHPXray\Segment'
```

## Instrumentation

### Guzzle

If you want to instrument `Guzzle\Client` request, you can easily inject our `GuzzleHttp\HandlerStack` to your
service definition. In this way each request will be instrumented and the `trace-id` will be passed to the endpoint.

```yaml

client:
  class: GuzzleHttp\Client
  arguments:
    - handler: '@GuzzleHttp\HandlerStack'
```

## How to test the software

You can run the library test suite with [PHPUnit](https://phpunit.de/) by running `composer test` script, you can also run `composer mutation` script for mutation testing report.

## TODO

- [ ] Find a solution to pass an optional parent segment to each `Guzzle\Client` request
- [ ] Give the possibility to pass custom annotation to a `Guzzle\CLient` request
- [ ] Instrument `DynamoDb` calls 

## Getting help

If you have questions, concerns, bug reports, etc, please file an issue in this repository's Issue Tracker.

## Getting involved

Feedbacks and pull requests are very welcome, more on _how_ to contribute on [CONTRIBUTING](CONTRIBUTING.md).

----
