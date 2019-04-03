<?php
declare(strict_types = 1);

namespace Fol\Tests;

use Fol\App;
use Zend\Diactoros\Uri;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Datetime;

class AppTest extends TestCase
{
    public function testConfig()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->set('config', [1]);
        $this->assertSame([1], $app->get('config'));

        $this->assertEquals('http://domain.com/www', (string) $app->getUri());
        $this->assertEquals('http://domain.com/www/these/more/subdirectories', (string) $app->getUri('these/are', '../more/', '/subdirectories'));

        $this->assertEquals(__DIR__, $app->getPath());
        $this->assertEquals(__DIR__.'/subdirectory', $app->getPath('subdirectory'));
        $this->assertEquals(dirname(__DIR__).'/', $app->getPath('../'));
    }

    public function testContainer()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));
        $now = new Datetime('now');

        $app->set('now', $now);

        //Single
        $now2 = $app->get('now');
        $this->assertSame($now, $now2);
    }

    public function testNotFoundException()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->get('not-existing');
    }

    public function testContainerException()
    {
        $this->expectException(ContainerExceptionInterface::class);

        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->addFactory('fail', function () {
            return new UndefinedClass();
        });

        $app->get('fail');
    }

    public function testServiceProvider()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->addServiceProvider(new class implements ServiceProviderInterface {
            public function getFactories()
            {
                return [
                    'foo' => function (ContainerInterface $container) {
                        return 'bar';
                    }
                ];
            }

            public function getExtensions()
            {
                return [
                    'foo' => function (ContainerInterface $container, $previous) {
                        return $previous.'.modified';
                    }
                ];
            }
        });

        $this->assertSame('bar.modified', $app->get('foo'));
    }
}
