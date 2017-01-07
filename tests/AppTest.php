<?php

namespace Fol\Tests;

use Fol\App;
use Fol\ServiceProviderInterface;
use Zend\Diactoros\Uri;
use PHPUnit_Framework_TestCase;
use Datetime;

class AppTest extends PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app['config'] = function () {
            return [1];
        };

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

        $app['now'] = function () use ($now) {
            return $now;
        };

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

        $app1['now'] = function () {
            return new Datetime('now');
        };

        $app2['yesterday'] = function () {
            return new Datetime('-1 day');
        };

        $app['now-yesterday'] = function ($app) {
            return $app->get('now')->getTimestamp() - $app->get('yesterday')->getTimestamp();
        };

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
     * @expectedException Interop\Container\Exception\NotFoundException
     */
    public function testNotFoundException()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->get('not-existing');
    }

    /**
     * @expectedException Interop\Container\Exception\ContainerException
     */
    public function testContainerException()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->set('fail', function () {
            return new UndefinedClass();
        });

        $app->get('fail');
    }

    public function testServiceProvider()
    {
        $app = new App(__DIR__, new Uri('http://domain.com/www'));

        $app->register(new class implements ServiceProviderInterface {
            public function register(App $app)
            {
                $app['foo'] = 'bar';
            }
        });

        $this->assertSame('bar', $app->get('foo'));
    }
}
