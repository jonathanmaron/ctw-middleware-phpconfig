<?php
declare(strict_types=1);

namespace CtwTest\Middleware\PhpConfigMiddleware;

use Ctw\Middleware\PhpConfigMiddleware\Exception\UnexpectedValueException;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddleware;
use Ctw\Middleware\PhpConfigMiddleware\PhpConfigMiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;
use Middlewares\Utils\Dispatcher;

class PhpConfigMiddlewareTest extends AbstractCase
{
    public function testPhpConfigMiddleware(): void
    {
        $stack = [
            $this->getInstance(),
        ];

        Dispatcher::run($stack);

        $this->assertEquals('On', ini_get('assert.warning'));
        $this->assertEquals('1', ini_get('assert.active'));
        $this->assertEquals('', ini_get('assert.callback'));
    }

    public function testPhpConfigMiddlewareException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $config = [
            'invalid.invalid' => 'invalid',
        ];

        $middleware = new PhpConfigMiddleware();
        $middleware->setConfig($config);

        Dispatcher::run([$middleware]);
    }

    private function getInstance(): PhpConfigMiddleware
    {
        $config    = [
            PhpConfigMiddleware::class => [
                'assert.warning'  => true,
                'assert.active'   => 1,
                'assert.callback' => null,
            ],
        ];
        $container = new ServiceManager();
        $container->setService('config', $config);

        $factory = new PhpConfigMiddlewareFactory();

        return $factory->__invoke($container);
    }
}
