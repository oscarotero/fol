# Fol

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-scrutinizer]][link-scrutinizer]

This is a simple class with some utils to build websites with the following features:

## Paths and URI

Manage the path of the app and the public uri using the `UriInterface` from [PSR-7](http://www.php-fig.org/psr/psr-7/):

```php
use Fol\App;
use Zend\Diactoros\Uri;

$path = '/var/www/my-website';
$uri = new Uri('http://localhost/my-website');

$app = new App($path, $uri);

//Get the path
$app->getPath(); // /var/www/my-website
$app->getPath('dir/subdir', '../other'); // /var/www/my-website/dir/other

//Get the uri
(string) $app->getUri(); // http://localhost/my-website
(string) $app->getUri('post/1', 'details'); // http://localhost/my-website/post/1/details
```

## Container interop

It's compatible with [container-interop](https://github.com/container-interop/container-interop) and [service-provider](https://github.com/container-interop/service-provider), and allows to nest other containers:

```php
use Fol\App;
use Zend\Diactoros\Uri;

$app = new App(__DIR__, new Uri('http://localhost/my-website'));

//Set a value
$app->set('database.config', [
    'user' => 'foo',
    'pass' => 'bar'
]);

//Get the value
$config = $app->get('database.config');

//Set a service
$app->addService('database', function ($app) {
    return new DatabaseClass($app->get('database.config'));
});

//Get the service value
$database = $app->get('database');

//Add other sub-containers compatible with Container-Interop
$app->addContainer($container);

//And add ServiceProviderInterface instances to register several dependencies
$app->addServiceProvider(new MyServiceProvider());
```

---

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/oscarotero/fol.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/oscarotero/fol/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/g/oscarotero/fol.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/oscarotero/fol
[link-travis]: https://travis-ci.org/oscarotero/fol
[link-scrutinizer]: https://scrutinizer-ci.com/g/oscarotero/fol
