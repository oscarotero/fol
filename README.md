# Fol

This is a simple class with some utils to build websites with the following features:

## Paths and URI

Manage the path of the app and the public uri using the `UriInterface` from [PSR-7](http://www.php-fig.org/psr/psr-7/):

```php
use Fol\App;
use Zend\Diactoros\Uri;

$path = /var/www/my-website;
$uri = new Uri('http://localhost/my-website');

$app = new App($path, $uri);

//Get the path
$app->getPath(); // /var/www/my-website
$app->getPath('dir/subdir', '../other'); // /var/www/my-website/dir/other

//Get the uri
(string) $app->getUri(); // http://localhost/my-website
(string) $app->getUri('post/1', 'details'); // http://localhost/my-website/post/1/ver
```

## Container-interop

It's compatible with [container-interop](https://github.com/container-interop/container-interop), and allows to add other containers:

```php
use Fol\App;
use Zend\Diactoros\Uri;

$app = new App(__DIR__, new Uri('http://localhost/my-website'));

//Set a dependency
$app->set('database', function () {
    return new MyDatabaseClass($config);
});

//Get a dependency
$database = $app->get('database');

//You can use arrayAccess to get/set dependencies
$app['templates'] = function () {
    return new TemplatesEngine();
};

$templates = $app['templates'];

//And add other sub-containers compatible with Container-Interop
$app->addContainer($container);

//And add ServiceProviderInterface instances to register several dependencies
$app->register(new MyServiceProvider());
```

That's all.