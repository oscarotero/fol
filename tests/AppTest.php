<?php

namespace Fol\Tests;

use Fol\App;
use Zend\Diactoros\Uri;
use Interop\Container\ServiceProvider;
use Psr\Container\ContainerInterface;
use PHPUnit_Framework_TestCase;
use Datetime;

class AppTest extends PHPUnit_Framework_TestCase
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

    public function testMultipleContainer()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));
        $app1 = new App(__DIR__, new Uri('http://domain.com/www'));
        $app2 = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->addContainer($app1);
        $app->addContainer($app2);

        $app1->set('now', new Datetime('now'));

        $app2->set('yesterday', new Datetime('-1 day'));

        $app->addService('now-yesterday', function ($app) {
            return $app->get('now')->getTimestamp() - $app->get('yesterday')->getTimestamp();
        });

        //Single
        $now = $app->get('now');
        $this->assertInstanceOf('Datetime', $now);
        $this->assertSame(time(), $now->getTimestamp());

        //Multiple
        $yesterday = $app->get('yesterday');
        $this->assertInstanceOf('Datetime', $yesterday);
        $this->assertSame(strtotime('-1 day'), $yesterday->getTimestamp());

        //Combined
        $substract = $app->get('now-yesterday');
        $this->assertEquals(3600 * 24, $substract);
    }

    /**
     * @expectedException Psr\Container\NotFoundExceptionInterface
     */
    public function testNotFoundException()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->get('not-existing');
    }

    /**
     * @expectedException Psr\Container\ContainerExceptionInterface
     */
    public function testContainerException()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->addService('fail', function () {
            return new UndefinedClass();
        });

        $app->get('fail');
    }

    public function testServiceProvider()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->addServiceProvider(new class implements ServiceProvider {
            public function getServices()
            {
                return [
                    'foo' => function (ContainerInterface $container, callable $previous = null) {
                        return 'bar';
                    }
                ];
            }
        });

        $app->addServiceProvider(new class implements ServiceProvider {
            public function getServices()
            {
                return [
                    'foo' => function (ContainerInterface $container, callable $previous = null) {
                        return $previous().'.modified';
                    }
                ];
            }
        });

        $this->assertSame('bar.modified', $app->get('foo'));
    }
}
